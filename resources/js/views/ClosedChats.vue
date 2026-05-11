<template>
  <div class="container mx-auto p-4">
    <h1 class="text-2xl font-bold mb-4">Closed Conversations</h1>
    <div class="flex border rounded-lg h-[75vh] bg-white shadow-md">
      <!-- Chat List Panel -->
      <div class="w-1/3 border-r flex flex-col">
        <div class="p-4 border-b font-bold flex-shrink-0">Archived Chats</div>
        <div v-if="loadingChats" class="flex-grow flex items-center justify-center">
            <svg class="animate-spin h-8 w-8 text-gray-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
        </div>
        <ul v-else class="overflow-y-auto flex-grow">
          <li
            v-for="chat in chats"
            :key="chat.id"
            @click="selectChat(chat)"
            class="p-4 cursor-pointer hover:bg-gray-100 border-b"
            :class="{ 'bg-indigo-100': selectedChat && selectedChat.id === chat.id }"
          >
            <div class="font-semibold">
              User ({{ chat.ip_address || chat.user?.name || 'Guest' }})
            </div>
            <div class="text-sm text-gray-500 truncate">
              ID: {{ chat.session_id.substring(0, 8) }}
            </div>
            <div class="text-xs text-gray-400">
              Closed on: {{ new Date(chat.updated_at).toLocaleString() }}
            </div>
          </li>
          <li v-if="chats.length === 0" class="p-4 text-center text-gray-500">
            No closed chats found.
          </li>
        </ul>
        <!-- Pagination Controls -->
        <div v-if="!loadingChats && pagination.last_page > 1" class="p-2 border-t flex justify-between items-center bg-gray-50 flex-shrink-0">
            <button @click="fetchClosedChats(pagination.current_page - 1)" :disabled="pagination.current_page <= 1" class="px-3 py-1 text-sm rounded bg-gray-200 hover:bg-gray-300 disabled:opacity-50"> Previous </button>
            <span class="text-xs text-gray-500"> Page {{ pagination.current_page }} of {{ pagination.last_page }} </span>
            <button @click="fetchClosedChats(pagination.current_page + 1)" :disabled="pagination.current_page >= pagination.last_page" class="px-3 py-1 text-sm rounded bg-gray-200 hover:bg-gray-300 disabled:opacity-50"> Next </button>
        </div>
      </div>

      <!-- Message Display Area -->
      <div class="w-2/3 flex flex-col">
        <div v-if="selectedChat" class="flex flex-col h-full">
          <div class="p-4 border-b font-bold flex justify-between items-center flex-shrink-0">
            <span>Chat with User ({{ selectedChat.ip_address || selectedChat.user?.name || 'Guest' }})</span>
            <span class="text-xs font-semibold text-gray-500 uppercase">Closed</span>
          </div>
          <div v-if="loadingMessages" class="flex-grow flex items-center justify-center text-gray-500">
            <svg class="animate-spin h-8 w-8 text-gray-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
          </div>
          <div v-else class="flex-grow p-4 overflow-y-auto bg-gray-50" ref="messagesContainer">
            <div v-for="message in messages" :key="message.id" class="mb-4 flex" :class="message.sender?.role === 'agent' || message.sender?.role === 'admin' ? 'justify-end' : 'justify-start'">
              <div class="max-w-md">
                <div class="px-4 py-2 rounded-lg" :class="message.sender?.role === 'agent' || message.sender?.role === 'admin' ? 'bg-indigo-500 text-white' : 'bg-gray-200 text-gray-800'">
                  <p>{{ message.content }}</p>
                </div>
                <div class="text-xs text-gray-400 mt-1" :class="message.sender?.role === 'agent' || message.sender?.role === 'admin' ? 'text-right' : 'text-left'">
                  {{ message.sender?.name || 'Guest' }}
                </div>
              </div>
            </div>
          </div>
        </div>
        <div v-else class="flex items-center justify-center h-full text-gray-500">
          <p>Select a conversation from the left panel to view the archive.</p>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
// Archive Chats (Read-only)
import { ref, onMounted, nextTick } from 'vue';
import api from '@/services/api';

const chats = ref([]); // Archive list
const selectedChat = ref(null); // Chat to view
const messages = ref([]); // Archived messages (readonly)
const pagination = ref({});
const messagesContainer = ref(null);
const loadingChats = ref(false); // Spinners for loading visualization
const loadingMessages = ref(false);

// Auto-scroll to bottom after DOM update
const scrollToBottom = () => {
  nextTick(() => {
    if (messagesContainer.value) {
      messagesContainer.value.scrollTop = messagesContainer.value.scrollHeight;
    }
  });
};

// Fetch closed chats with pagination
const fetchClosedChats = async (page = 1) => {
  loadingChats.value = true;
  try {
    const response = await api.get(`/agent/closed-chats?page=${page}`);
    chats.value = response.data.data;
    pagination.value = {
        current_page: response.data.current_page,
        last_page: response.data.last_page,
        total: response.data.total,
    };
  } catch (error) {
    console.error('Error fetching archive:', error);
  } finally {
    loadingChats.value = false;
  }
};

// Select a chat and open its content
const selectChat = async (chat) => {
  selectedChat.value = chat;
  messages.value = []; // Quick clear
  await fetchMessages(chat.id);
  scrollToBottom();
};

const fetchMessages = async (chatId) => {
  loadingMessages.value = true;
  try {
    // The same endpoint returns both closed and live chats
    const response = await api.get(`/agent/chats/${chatId}/messages`);
    messages.value = response.data;
  } catch (error) {
    console.error('Error fetching messages:', error);
  } finally {
    loadingMessages.value = false; // End loading
  }
};

onMounted(() => {
  fetchClosedChats();
});
</script>

<style scoped>
/* Scoped styles can be added here if needed */
</style>
