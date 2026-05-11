<template>
  <div class="container mx-auto p-4">
    <div class="flex justify-between items-center mb-4">
      <h1 class="text-2xl font-bold">Agent Chat Dashboard</h1>
      <router-link to="/agent/closed-chats" class="text-sm font-medium text-indigo-600 hover:text-indigo-500">
        View Closed Chats Archive &rarr;
      </router-link>
    </div>
    <div class="flex border rounded-lg h-[75vh] bg-white shadow-md">
      <!-- Chat List Panel -->
      <div class="w-1/3 border-r">
        <div class="p-4 border-b font-bold">Conversations</div>
        <ul class="overflow-y-auto h-[calc(75vh-57px)]">
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
            <div class="text-xs text-gray-400 uppercase">{{ chat.status }}</div>
          </li>
          <li v-if="chats.length === 0" class="p-4 text-center text-gray-500">
            No active chats.
          </li>
        </ul>
        <!-- Pagination Controls for the chat list -->
        <div v-if="chatPagination.last_page > 1" class="p-2 border-t flex justify-between items-center bg-gray-50">
            <button
                @click="fetchChats(chatPagination.current_page - 1)"
                :disabled="chatPagination.current_page <= 1"
                class="px-3 py-1 text-sm rounded bg-gray-200 hover:bg-gray-300 disabled:opacity-50 disabled:cursor-not-allowed"
            > Previous </button>
            <span class="text-xs text-gray-500">
                Page {{ chatPagination.current_page }} of {{ chatPagination.last_page }}
            </span>
            <button
                @click="fetchChats(chatPagination.current_page + 1)"
                :disabled="chatPagination.current_page >= chatPagination.last_page"
                class="px-3 py-1 text-sm rounded bg-gray-200 hover:bg-gray-300 disabled:opacity-50 disabled:cursor-not-allowed"
            > Next </button>
        </div>
      </div>

      <!-- Message Display and Input Area -->
      <div class="w-2/3 flex flex-col">
        <div v-if="selectedChat" class="flex flex-col h-full">
          <div class="p-4 border-b font-bold flex justify-between items-center">
            <span>Chat with User ({{ selectedChat.ip_address || selectedChat.user?.name || 'Guest' }})</span>
            <button @click="closeSelectedChat" class="px-3 py-1 text-xs bg-red-500 text-white rounded hover:bg-red-600">Close Chat</button>
          </div>
          <div class="flex-grow p-4 overflow-y-auto bg-gray-50" ref="messagesContainer">
            <div v-for="message in messages" :key="message.id" class="mb-4 flex" :class="message.sender_id === authStore.user?.id ? 'justify-end' : 'justify-start'">
              <div class="max-w-md">
                <div class="px-4 py-2 rounded-lg" :class="message.sender_id === authStore.user?.id ? 'bg-indigo-500 text-white' : 'bg-gray-200 text-gray-800'">
                  <p>{{ message.content }}</p>
                </div>
                <div class="text-xs text-gray-400 mt-1" :class="message.sender_id === authStore.user?.id ? 'text-right' : 'text-left'">
                  {{ message.sender?.name || 'Guest' }}
                </div>
              </div>
            </div>
          </div>
          <div class="p-4 border-t bg-white">
            <form @submit.prevent="sendMessage">
              <div class="flex">
                <input
                  v-model="newMessage"
                  type="text"
                  placeholder="Type your message..."
                  class="flex-grow border rounded-l-md px-4"
                  autocomplete="off"
                />
                <button type="submit" class="bg-indigo-600 text-white px-6 py-2 rounded-r-md hover:bg-indigo-700">Send</button>
              </div>
            </form>
          </div>
        </div>
        <div v-else class="flex items-center justify-center h-full text-gray-500">
          <p>Select a conversation from the left panel.</p>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
// Using <script setup>, all variables and imports defined here 
// are automatically available in the HTML (template) section above.

import { ref, onMounted, onUnmounted, nextTick, watch } from 'vue';
import api from '@/services/api';
// Import the Pinia Store. No more messy global variables; 'useAuthStore' provides login info.
import { useAuthStore } from '@/stores/auth';

// Initialize the Auth Store. Used in template as "authStore.user?.id"
const authStore = useAuthStore();

// === REACTIVE STATE ===
const chats = ref([]); // All active chats for the left panel.
const selectedChat = ref(null); // Currently selected chat (center window).
const messages = ref([]); // Messages for the selected chat.
const newMessage = ref(''); // Input text for the agent's response.
const chatPagination = ref({}); // Pagination data (current_page, last_page).
const messagesContainer = ref(null);
let pollingInterval = null;

// Scroll to bottom of message window when a new message arrives.
const scrollToBottom = () => {
  nextTick(() => {
    if (messagesContainer.value) {
      messagesContainer.value.scrollTop = messagesContainer.value.scrollHeight;
    }
  });
};

watch(messages, scrollToBottom, { deep: true });

// Fetch active conversations (Paginated)
const fetchChats = async (page = 1) => {
  try {
    const response = await api.get(`/agent/chats?page=${page}`);
    chats.value = response.data.data;
    
    // Update defensive pagination object used by HTML 'v-if' directives for buttons.
    chatPagination.value = {
        current_page: response.data.current_page,
        last_page: response.data.last_page,
        total: response.data.total,
    };
  } catch (error) {
    console.error('Error fetching chats:', error);
  }
};

// When a list item is clicked on the left side
const selectChat = async (chat) => {
  console.log('Chat selected:', chat);

  // Before joining a new chat, unsubscribe from the PREVIOUS websocket (Echo) channel.
  if (selectedChat.value) {
    window.Echo.leave(`chat.${selectedChat.value.session_id}`);
  }
  
  selectedChat.value = chat; // Reactive update (changes selection color in list)
  messages.value = []; // Clear current window while new messages load.

  await fetchMessages(chat.id);
  listenForMessages(chat.session_id); // Join the new real-time channel.
};

// Fetch message history for a given Chat ID.
const fetchMessages = async (chatId) => {
  try {
    const response = await api.get(`/agent/chats/${chatId}/messages`);
    messages.value = response.data;
  } catch (error) {
    console.error('Error fetching messages:', error);
  }
};

// Send message as Agent to the customer.
const sendMessage = async () => {
    if (!newMessage.value.trim() || !selectedChat.value) return; // Don't send empty messages.

    const messageToSend = newMessage.value;
    newMessage.value = ''; // Clear input for speed (Better UX)

    try {
        const response = await api.post(`/agent/chats/${selectedChat.value.id}/message`, {
            content: messageToSend,
        });
        // Check if WebSocket (Echo) already added this message
        if (!messages.value.some(m => m.id === response.data.id)) {
            messages.value.push(response.data);
        }
    } catch (error) {
        console.error('Send error:', error);
        
        // 409 Conflict if another agent took the chat simultaneously.
        if (error.response && error.response.status === 409) {
            alert('This conversation has already been taken by another operator!');
            
            // Remove from list as it's no longer ours.
            const index = chats.value.findIndex(c => c.id === selectedChat.value.id);
            if (index > -1) {
                chats.value.splice(index, 1);
            }
            selectedChat.value = null; // Close center window.
            messages.value = [];
        } else {
            alert('An error occurred. Please try again.');
            newMessage.value = messageToSend; // Restore text.
        }
    }
};

// Final closure of the conversation
const closeSelectedChat = async () => {
    if (!selectedChat.value) return;
    
    // JS built-in confirm() for "are you sure?" dialog.
    if (confirm('Are you sure you want to archive/close this chat?')) {
        try {
            await api.post(`/agent/chats/${selectedChat.value.id}/close`);
            selectedChat.value.status = 'closed';
            
            // Remove from list after successful API call
            const index = chats.value.findIndex(c => c.id === selectedChat.value.id);
            if (index > -1) {
                chats.value.splice(index, 1);
            }
            selectedChat.value = null; // Clear main window.
        } catch (error) {
            console.error('Closure error:', error);
            alert('Could not close the chat (Perhaps you don\'t have Admin/Agent permissions?).');
        }
    }
};

// Start real-time monitoring for the selected conversation
const listenForMessages = (sessionId) => {
  window.Echo.channel(`chat.${sessionId}`)
    .listen('NewChatMessage', (event) => {
        // Important check! Is the response for the room currently open?
        if (selectedChat.value && selectedChat.value.session_id === sessionId) {
            if (!messages.value.some(m => m.id === event.message.id)) {
                messages.value.push(event.message); // Push to list, Vue renders it.
            }
        }
    });
};

// Although WebSocket is ideal, we poll the CHAT LIST every 10s 
// in onMounted because new chat requests might come in via API.
onMounted(() => {
  fetchChats();

  pollingInterval = setInterval(() => {
    // Reload list on current page.
    fetchChats(chatPagination.value.current_page || 1);
  }, 10000); 
});

onUnmounted(() => {
  // Clean up listeners when navigating away
  if (selectedChat.value) {
    window.Echo.leave(`chat.${selectedChat.value.session_id}`);
  }
  if (pollingInterval) clearInterval(pollingInterval);
});
</script>
