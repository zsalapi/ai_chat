<template>
  <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
    <!-- Header Section -->
    <div class="sm:flex sm:items-center sm:justify-between mb-10 bg-white p-6 rounded-2xl shadow-sm border border-gray-100">
      <div>
        <h1 class="text-3xl font-extrabold text-transparent bg-clip-text bg-gradient-to-r from-indigo-600 to-purple-600 tracking-tight">Event Management</h1>
        <p class="mt-2 text-sm text-gray-500 font-medium">Browse, add, modify or delete your own events.</p>
      </div>
      <div class="mt-5 sm:mt-0">
        <button @click="openCreateModal" class="inline-flex items-center justify-center rounded-xl bg-gradient-to-r from-indigo-600 to-purple-600 px-5 py-2.5 text-sm font-semibold text-white shadow-md hover:shadow-lg hover:from-indigo-500 hover:to-purple-500 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 sm:w-auto transition-all transform hover:-translate-y-0.5 duration-200">
          <svg class="-ml-1 mr-2 h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
            <path fill-rule="evenodd" d="M10 5a1 1 0 011 1v3h3a1 1 0 110 2h-3v3a1 1 0 11-2 0v-3H6a1 1 0 110-2h3V6a1 1 0 011-1z" clip-rule="evenodd" />
          </svg>
          New event
        </button>
      </div>
    </div>

    <!-- Error State -->
    <transition enter-active-class="transform ease-out duration-300 transition" enter-from-class="-translate-y-2 opacity-0" enter-to-class="translate-y-0 opacity-100" leave-active-class="transition ease-in duration-100" leave-from-class="opacity-100" leave-to-class="opacity-0">
      <div v-if="eventStore.error" class="rounded-xl bg-red-50 p-4 mb-8 shadow-sm border border-red-200 flex items-center">
        <div class="flex-shrink-0">
          <svg class="h-6 w-6 text-red-500" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
          </svg>
        </div>
        <div class="ml-3">
          <h3 class="text-sm font-semibold text-red-800">{{ eventStore.error }}</h3>
        </div>
      </div>
    </transition>

    <!-- Loading State -->
    <div v-if="eventStore.loading && !eventStore.events.length" class="flex flex-col justify-center items-center py-32">
      <div class="animate-spin rounded-full h-14 w-14 border-t-2 border-b-2 border-indigo-600 mb-4"></div>
      <p class="text-gray-500 font-medium animate-pulse">Loading data...</p>
    </div>

    <!-- Data Table -->
    <div v-if="!eventStore.loading || eventStore.events.length > 0" class="mt-4 flex flex-col">
      <div class="-my-2 -mx-4 overflow-x-auto sm:-mx-6 lg:-mx-8">
        <div class="inline-block min-w-full py-2 align-middle md:px-6 lg:px-8">
          <div class="overflow-hidden shadow-xl ring-1 ring-black ring-opacity-5 md:rounded-2xl bg-white backdrop-blur-xl bg-opacity-80">
            <table class="min-w-full divide-y divide-gray-100">
              <thead class="bg-gray-50/80">
                <tr>
                  <th scope="col" class="py-4 pl-4 pr-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider sm:pl-6">Title</th>
                  <th scope="col" class="px-3 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Occurrence</th>
                  <th scope="col" class="px-3 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider hidden md:table-cell">Description</th>
                  <th scope="col" class="relative py-4 pl-3 pr-4 sm:pr-6 text-right text-xs font-bold text-gray-500 uppercase tracking-wider">Actions</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-gray-100 bg-white">
                <tr v-if="eventStore.events.length === 0">
                  <td colspan="4" class="py-16 text-center text-base text-gray-500 font-medium">
                    <div class="flex items-center justify-center flex-col">
                      <svg class="w-12 h-12 text-gray-300 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                      No events to display.
                    </div>
                  </td>
                </tr>
                <tr v-for="event in eventStore.events" :key="event.id" class="hover:bg-indigo-50/30 transition-colors duration-150 group">
                  <td class="whitespace-nowrap py-5 pl-4 pr-3 text-sm font-semibold text-gray-900 sm:pl-6">{{ event.title }}</td>
                  <td class="whitespace-nowrap px-3 py-5 text-sm text-gray-700 font-semibold">{{ formatDate(event.occurrence) }}</td>
                  <td class="px-3 py-5 text-sm text-gray-500 hidden md:table-cell max-w-xs truncate">{{ event.description }}</td>
                  <td class="relative whitespace-nowrap py-5 pl-3 pr-4 text-right text-sm font-medium sm:pr-6">
                    <button @click="openEditModal(event)" class="text-indigo-600 hover:text-indigo-900 mr-4 font-semibold transition-colors opacity-80 group-hover:opacity-100">Edit<span class="sr-only">, {{ event.title }}</span></button>
                    <button @click="confirmDelete(event.id)" class="text-red-500 hover:text-red-700 font-semibold transition-colors opacity-80 group-hover:opacity-100">Delete<span class="sr-only">, {{ event.title }}</span></button>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>

    <!-- Event Modal overlay with Transition -->
    <transition enter-active-class="ease-out duration-300" enter-from-class="opacity-0" enter-to-class="opacity-100" leave-active-class="ease-in duration-200" leave-from-class="opacity-100" leave-to-class="opacity-0">
      <div v-if="isModalOpen" class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <div class="flex min-h-screen items-center justify-center p-4 text-center sm:p-0">
          
          <!-- Glassmorphism Background backdrop -->
          <div class="fixed inset-0 bg-gray-900 bg-opacity-40 backdrop-blur-sm transition-opacity" aria-hidden="true" @click="closeModal"></div>

          <!-- Modal panel -->
          <transition enter-active-class="ease-out duration-300 transform" enter-from-class="opacity-0 translate-y-4 sm:translate-y-8 sm:scale-95" enter-to-class="opacity-100 translate-y-0 sm:scale-100" leave-active-class="ease-in duration-200 transform" leave-from-class="opacity-100 translate-y-0 sm:scale-100" leave-to-class="opacity-0 translate-y-4 sm:translate-y-8 sm:scale-95">
            <div v-if="isModalOpen" class="relative transform overflow-hidden rounded-2xl bg-white text-left shadow-2xl transition-all sm:my-8 sm:w-full sm:max-w-lg border border-gray-100">
              
              <!-- Modal Header -->
              <div class="bg-gradient-to-r from-indigo-50 to-purple-50 px-6 py-5 border-b border-gray-100">
                <h3 class="text-xl font-bold leading-6 text-gray-900 flex items-center" id="modal-title">
                  <span class="bg-white p-2 rounded-lg shadow-sm mr-3">
                    <svg v-if="isEditing" class="w-5 h-5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                    <svg v-else class="w-5 h-5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path></svg>
                  </span>
                  {{ isEditing ? 'Edit Event' : 'Create New Event' }}
                </h3>
              </div>

              <!-- Form Body -->
              <div class="bg-white px-6 py-6 sm:p-6">
                <form @submit.prevent="saveEvent" class="space-y-6">
                  <div v-if="!isEditing">
                    <label for="title" class="block text-sm font-semibold text-gray-700">Title</label>
                    <input type="text" id="title" v-model="form.title" required class="mt-2 block w-full rounded-xl border border-gray-200 shadow-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200 focus:ring-opacity-50 sm:text-sm py-3 px-4 transition-colors bg-gray-50 focus:bg-white" placeholder="e.g. Meeting" />
                  </div>
                  
                  <div v-if="!isEditing">
                    <label for="occurrence" class="block text-sm font-semibold text-gray-700">Occurrence</label>
                    <input type="datetime-local" id="occurrence" v-model="form.occurrence" required class="mt-2 block w-full rounded-xl border border-gray-200 shadow-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200 focus:ring-opacity-50 sm:text-sm py-3 px-4 transition-colors bg-gray-50 focus:bg-white" />
                  </div>
                  
                  <div>
                    <label for="description" class="block text-sm font-semibold text-gray-700">Description</label>
                    <textarea id="description" v-model="form.description" rows="3" class="mt-2 block w-full rounded-xl border border-gray-200 shadow-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200 focus:ring-opacity-50 sm:text-sm py-3 px-4 transition-colors bg-gray-50 focus:bg-white" placeholder="Event details..."></textarea>
                  </div>
                </form>
              </div>

              <!-- Modal Footer -->
              <div class="bg-gray-50 px-6 py-4 sm:flex sm:flex-row-reverse rounded-b-2xl border-t border-gray-100">
                <button type="button" @click="saveEvent" :disabled="isSaving" class="inline-flex w-full justify-center rounded-xl border border-transparent bg-indigo-600 px-6 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 sm:ml-3 sm:w-auto transition-all disabled:opacity-60 disabled:cursor-not-allowed">
                  <span v-if="isSaving" class="mr-2">
                    <svg class="animate-spin h-4 w-4 text-white inline-block" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                      <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                      <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                  </span>
                  {{ isEditing ? 'Save' : 'Create' }}
                </button>
                <button type="button" @click="closeModal" class="mt-3 inline-flex w-full justify-center rounded-xl border border-gray-300 bg-white px-6 py-2.5 text-sm font-semibold text-gray-700 shadow-sm hover:bg-gray-50 hover:text-gray-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 sm:mt-0 sm:ml-3 sm:w-auto transition-colors">
                  Cancel
                </button>
              </div>
            </div>
          </transition>
        </div>
      </div>
    </transition>
  </div>
</template>

<script setup>
// Advanced CRUD (Create, Read, Update, Delete) view using Pinia eventStore
import { ref, reactive, onMounted } from 'vue';
import { useEventStore } from '@/stores/event'; // The dedicated Pinia module for event management

const eventStore = useEventStore();

// === MODAL WINDOW STATES ===
const isModalOpen = ref(false);
const isEditing = ref(false); // If true, "Edit" mode; if false, "Create" mode
const isSaving = ref(false); // For spinner during save
const currentEventId = ref(null); // ID of the element being edited

// Assemble data to be submitted into a reactive object.
const form = reactive({
  title: '',
  occurrence: '',
  description: ''
});

// Fetch all events from API when the view loads
onMounted(() => {
  eventStore.fetchEvents();
});

// Date and Time formatter (converts raw backend string to human US styled date)
function formatDate(dateStr) {
  if (!dateStr) return '';
  const date = new Date(dateStr);
  return date.toLocaleString('en-US', { // Changed to US locale
    year: 'numeric',
    month: 'long',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  });
}

// === BUTTON CLICK EVENTS ===

// Open new event modal
function openCreateModal() {
  isEditing.value = false;
  currentEventId.value = null;
  resetForm(); // Clear the form so previous text doesn't stay.
  isModalOpen.value = true;
}

// Edit button
function openEditModal(event) {
  isEditing.value = true;
  currentEventId.value = event.id;
  
  // Copy data of the specific element into form inputs.
  form.title = event.title;
  form.occurrence = event.occurrence.slice(0, 16); // Adjusted for "datetime-local" input field format
  form.description = event.description || '';
  
  isModalOpen.value = true;
}

// Close modal window
function closeModal() {
  isModalOpen.value = false;
  resetForm();
}

function resetForm() {
  form.title = '';
  form.occurrence = '';
  form.description = '';
}

// === PROCESS SUBMITTED DATA ===
async function saveEvent() {
  // Built-in Validation - If title or occurrence is missing.
  if (!isEditing.value && (!form.title || !form.occurrence)) {
    alert("Please fill in the title and the date!");
    return;
  }

  // When editing (per backend requirement)
  if (isEditing.value && !form.description) {
      alert("Description is required when editing!");
      return;
  }
  
  isSaving.value = true; // Start spinner icon
  try {
    if (isEditing.value) {
      // Call Pinia Store action (API Update request runs in background)
      await eventStore.updateEvent(currentEventId.value, { description: form.description });
    } else {
      // Call Pinia Store action (Create new)
      await eventStore.createEvent(form);
    }
    closeModal(); // Close after successful save.
  } catch (error) {
    if (error.response && error.response.data && error.response.data.message) {
      alert(`Error: ${error.response.data.message}`);
    } else {
      alert("An error occurred during save. Please try again.");
    }
  } finally {
    isSaving.value = false; // Stop spinner
  }
}

// Delete button confirmation
async function confirmDelete(id) {
  if (window.confirm("Are you sure you want to delete this event?")) {
    try {
      await eventStore.deleteEvent(id);
    } catch (error) {
      alert("An error occurred during deletion.");
    }
  }
}
</script>
