import { defineStore } from 'pinia';
import api from '@/services/api'; // Reference to our global, improved Axios instance

// Events state manager (Pinia Store)
export const useEventStore = defineStore('event', {
  // "state" is the collection of data stored here
  state: () => ({
    events: [],      // List of all loaded events
    loading: false,  // Is it currently loading from the network? (Good for spinner)
    error: null,     // Possible error messages
  }),
  
  // "actions" are methods that modify this data using API calls
  actions: {
    // 1. FETCH all events from the server
    async fetchEvents() {
      this.loading = true; // Turn on the spinner in UI
      this.error = null;
      try {
        const response = await api.get('/events');
        
        // If successful, put the data sent back by Laravel into memory (state.events)
        if (response.data && response.data.data) {
          this.events = response.data.data;
        } else {
          this.events = [];
        }
      } catch (err) {
        this.error = 'An error occurred while loading events.';
        console.error(err);
      } finally {
        this.loading = false; // Turn off the spinner in all cases
      }
    },
    
    // 2. CREATE a new event
    async createEvent(eventData) {
      try {
        const response = await api.post('/events', eventData); // POST request
        await this.fetchEvents(); // Immediately refresh the list to see current data
        return response.data;
      } catch (err) {
        console.error('Creation error:', err);
        throw err; // Pass error back to component
      }
    },
    
    // 3. MODIFY an event (using PUT HTTP method)
    async updateEvent(id, eventData) {
      try {
        const response = await api.put(`/events/${id}`, eventData);
        await this.fetchEvents(); // Refresh list after update
        return response.data;
      } catch (err) {
        console.error('Modification error:', err);
        throw err;
      }
    },
    
    // 4. DELETE an event (DELETE HTTP method)
    async deleteEvent(id) {
      try {
        await api.delete(`/events/${id}`);
        await this.fetchEvents(); // Refresh list after deletion
      } catch (err) {
        console.error('Deletion error:', err);
        throw err;
      }
    }
  }
});
