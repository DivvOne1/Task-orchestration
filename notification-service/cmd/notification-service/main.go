package main

import (
	"context"
	"database/sql"
	"encoding/json"
	"errors"
	"fmt"
	"log"
	"net/http"
	"os"
	"os/signal"
	"syscall"
	"time"

	_ "github.com/lib/pq"
	amqp "github.com/rabbitmq/amqp091-go"
)

type NotificationEvent struct {
	Type      string          `json:"type"`
	UserID    int64           `json:"user_id"`
	TaskID    *int64          `json:"task_id,omitempty"`
	Message   string          `json:"message"`
	Payload   json.RawMessage `json:"payload,omitempty"`
	CreatedAt time.Time       `json:"created_at"`
}

func main() {
	logger := log.New(os.Stdout, "[notification-service] ", log.LstdFlags|log.Lshortfile)

	ctx, stop := signal.NotifyContext(context.Background(), syscall.SIGINT, syscall.SIGTERM)
	defer stop()

	db, err := openDB()
	if err != nil {
		logger.Fatalf("database connection failed: %v", err)
	}
	defer db.Close()

	if err := db.PingContext(ctx); err != nil {
		logger.Fatalf("database ping failed: %v", err)
	}

	queueName := getEnv("RABBITMQ_QUEUE", "notifications")
	rabbitURL := getEnv("RABBITMQ_URL", "amqp://guest:guest@rabbitmq:5672/")

	conn, channel, deliveryStream, err := openConsumer(queueName, rabbitURL)
	if err != nil {
		logger.Fatalf("rabbitmq setup failed: %v", err)
	}
	defer conn.Close()
	defer channel.Close()

	server := &http.Server{
		Addr:              ":" + getEnv("PORT", "8081"),
		ReadHeaderTimeout: 5 * time.Second,
		Handler: http.HandlerFunc(func(w http.ResponseWriter, r *http.Request) {
			if r.URL.Path != "/health" {
				http.NotFound(w, r)
				return
			}
			w.Header().Set("Content-Type", "application/json")
			_, _ = w.Write([]byte(`{"status":"ok","service":"notification-service"}`))
		}),
	}

	go func() {
		logger.Printf("healthcheck server listening on %s", server.Addr)
		if err := server.ListenAndServe(); err != nil && !errors.Is(err, http.ErrServerClosed) {
			logger.Fatalf("http server failed: %v", err)
		}
	}()

	go func() {
		for {
			select {
			case <-ctx.Done():
				return
			case delivery, ok := <-deliveryStream:
				if !ok {
					logger.Println("delivery channel closed")
					return
				}

				if err := handleDelivery(ctx, db, delivery); err != nil {
					logger.Printf("message processing failed: %v", err)
					_ = delivery.Nack(false, false)
					continue
				}

				logger.Printf("message processed successfully: delivery_tag=%d", delivery.DeliveryTag)
				_ = delivery.Ack(false)
			}
		}
	}()

	<-ctx.Done()
	logger.Println("shutdown signal received")

	shutdownCtx, cancel := context.WithTimeout(context.Background(), 10*time.Second)
	defer cancel()

	if err := server.Shutdown(shutdownCtx); err != nil {
		logger.Printf("http server shutdown error: %v", err)
	}
}

func openDB() (*sql.DB, error) {
	dsn := fmt.Sprintf(
		"host=%s port=%s user=%s password=%s dbname=%s sslmode=disable",
		getEnv("DB_HOST", "postgres"),
		getEnv("DB_PORT", "5432"),
		getEnv("DB_USER", "taskflow"),
		getEnv("DB_PASSWORD", "taskflow"),
		getEnv("DB_NAME", "taskflow"),
	)

	return sql.Open("postgres", dsn)
}

func openConsumer(queueName, rabbitURL string) (*amqp.Connection, *amqp.Channel, <-chan amqp.Delivery, error) {
	conn, err := amqp.Dial(rabbitURL)
	if err != nil {
		return nil, nil, nil, err
	}

	channel, err := conn.Channel()
	if err != nil {
		conn.Close()
		return nil, nil, nil, err
	}

	_, err = channel.QueueDeclare(queueName, true, false, false, false, nil)
	if err != nil {
		channel.Close()
		conn.Close()
		return nil, nil, nil, err
	}

	deliveryStream, err := channel.Consume(queueName, "", false, false, false, false, nil)
	if err != nil {
		channel.Close()
		conn.Close()
		return nil, nil, nil, err
	}

	return conn, channel, deliveryStream, nil
}

func handleDelivery(ctx context.Context, db *sql.DB, delivery amqp.Delivery) error {
	var event NotificationEvent

	if err := json.Unmarshal(delivery.Body, &event); err != nil {
		return fmt.Errorf("invalid payload: %w", err)
	}

	if event.Type == "" || event.UserID == 0 || event.Message == "" {
		return errors.New("event validation failed")
	}

	if event.CreatedAt.IsZero() {
		event.CreatedAt = time.Now().UTC()
	}

	if len(event.Payload) == 0 {
		event.Payload = json.RawMessage(`{}`)
	}

	_, err := db.ExecContext(
		ctx,
		`INSERT INTO notifications (user_id, type, message, payload, is_read, created_at, updated_at)
		 VALUES ($1, $2, $3, $4::jsonb, false, $5, $5)`,
		event.UserID,
		event.Type,
		event.Message,
		string(event.Payload),
		event.CreatedAt,
	)

	return err
}

func getEnv(key, fallback string) string {
	value := os.Getenv(key)
	if value == "" {
		return fallback
	}

	return value
}
