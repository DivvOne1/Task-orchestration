<script setup>
const notifications = [
  'You were added to the TaskFlow MVP project',
  'Task status changed to review',
  'Deadline is approaching for API auth flow',
]
</script>

<template>
  <section class="page">
    <div class="page-head">
      <div>
        <p class="eyebrow">Notifications</p>
        <h2>Async events feed</h2>
      </div>
      <button class="ghost-button" type="button">Mark all as read</button>
    </div>

    <div class="panel">
      <article v-for="notification in notifications" :key="notification" class="list-item">
        <div>
          <h4>{{ notification }}</h4>
          <p>RabbitMQ -> Go service -> PostgreSQL</p>
        </div>
      </article>
    </div>
  </section>
</template>
