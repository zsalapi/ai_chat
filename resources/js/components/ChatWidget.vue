<template>
  <div class="chat-widget">
    <!-- Chat bubble icon that opens the chat window -->
    <div v-if="!isOpen" @click="toggleChat" class="chat-bubble">
      <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="chat-icon">
        <path stroke-linecap="round" stroke-linejoin="round" d="M20.25 8.511c.884.284 1.5 1.128 1.5 2.097v4.286c0 1.136-.847 2.1-1.98 2.193l-3.72 3.72a1.125 1.125 0 01-1.59 0l-3.72-3.72A1.125 1.125 0 016 16.897V12.61c0-.97.616-1.813 1.5-2.097m6.006 0v-2.886c0-1.136.847-2.1 1.98-2.193l3.72-3.72a1.125 1.125 0 011.59 0l3.72 3.72A1.125 1.125 0 0124 5.103V9.39c0 .97-.616 1.813-1.5 2.097m-12.012 0v-2.886c0-1.136-.847-2.1-1.98-2.193L5.28 2.193a1.125 1.125 0 00-1.59 0L0 5.103a1.125 1.125 0 000 1.59l3.72 3.72c1.133.093 1.98.957 1.98 2.193v2.886c0 .97.616 1.813 1.5 2.097" />
      </svg>
    </div>

    <!-- The chat window that appears on click -->
    <div v-if="isOpen" class="chat-window">
      <div class="chat-header">
        <div class="flex items-center gap-2">
            <span class="text-sm font-semibold truncate" style="font-size: 0.9rem;">Support Chat</span>
            <button v-if="!isAgentRequested" @click="requestAgent" class="text-xs bg-white text-indigo-600 px-2 py-1 rounded shadow-sm hover:bg-gray-100 font-bold ml-2 whitespace-nowrap" style="font-size: 0.7rem;">
                Ask Agent
            </button>
        </div>
        <button @click="toggleChat" title="Close chat">×</button>
      </div>

      <div class="chat-messages" ref="messagesContainer">
        <div v-for="message in messages" :key="message.id" class="message" :class="{ 'is-agent': message.sender_id }">
          <div class="message-content">
            <p v-text="message.content"></p>
            <small v-if="message.sender">{{ message.sender.name }}</small>
            <small v-else-if="!message.sender_id && message.type === 'text'">You</small>
            <small v-else-if="message.type === 'bot'">Bot</small>
          </div>
        </div>
      </div>

      <div class="chat-input">
        <form @submit.prevent="sendMessage">
          <input v-model="newMessage" type="text" placeholder="Type a message..." :disabled="!sessionId" autocomplete="off" @keyup.enter="sendMessage" />
          <button type="submit" :disabled="!sessionId || !newMessage.trim()" title="Send message">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" style="width: 20px; height: 20px;"><path d="M3.105 2.289a.75.75 0 00-.826.95l1.414 4.625a.75.75 0 00.74.586h4.198a.75.75 0 010 1.5H4.433l-1.414 4.625a.75.75 0 00.826.95 28.896 28.896 0 0015.293-7.154.75.75 0 000-1.115A28.897 28.897 0 003.105 2.29z" /></svg>
          </button>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup>
// <script setup> is the latest and cleanest syntax for Vue 3 (Composition API).
// All logic (Javascript) that operates the HTML template above goes here.

// Import necessary Vue Composition API functions and the project's API client (axios).
import { ref, onMounted, onUnmounted, nextTick, watch } from 'vue';
import api from '@/services/api'; // API communication utility (Axios wrapper).

// === REACTIVE STATE ===
// 'ref' creates reactive variables. If its value changes, Vue automatically updates the DOM.
const isOpen = ref(false); // Is the chat window open?
const messages = ref([]); // Array of loaded and sent messages.
const newMessage = ref(''); // Bound to the input field (v-model="newMessage").
const sessionId = ref(sessionStorage.getItem('chat_session_id') || null); // Store Session ID for persistence.
const messagesContainer = ref(null); // Direct DOM reference for scrolling.
const isAgentRequested = ref(false); // Has an agent been requested?
let pollingInterval = null; // ID for background polling.

// === FUNCTIONS AND LOGIC ===

// Auto-scroll to bottom when a new message arrives.
const scrollToBottom = () => {
  // 'nextTick' waits for Vue to update the HTML before running.
  nextTick(() => {
    if (messagesContainer.value) {
      setTimeout(() => {
        messagesContainer.value.scrollTop = messagesContainer.value.scrollHeight;
      }, 100);
    }
  });
};

// Watcher for 'messages'. Runs scrollToBottom when it changes.
watch(messages, scrollToBottom, { deep: true });

// Toggle the chat widget.
const toggleChat = async () => {
  isOpen.value = !isOpen.value;
  // Start a new session if opening for the first time.
  if (isOpen.value && !sessionId.value) {
    await startChat();
  }
};

// Fetch endpoint to start chat on Laravel backend.
const startChat = async () => {
  try {
    const response = await api.post('/chat/start');
    sessionId.value = response.data.session_id; 
    sessionStorage.setItem('chat_session_id', sessionId.value); 
    isAgentRequested.value = response.data.chat.status !== 'open'; 
    await fetchMessages(); // Fetch initial/welcome messages.
    listenForMessages(); // Connect to WebSocket.
    startPolling(); // Fallback polling if WebSocket fails.
  } catch (error) {
    console.error('Error starting chat:', error);
  }
};

// Fetch previous messages from the server.
const fetchMessages = async () => {
  if (!sessionId.value) return;
  try {
    const response = await api.get(`/chat/${sessionId.value}/messages`);

    if (!response.data || !Array.isArray(response.data.messages)) {
        console.error("Invalid response from backend:", response.data);
        return;
    }

    // Filter only new messages to avoid duplicates.
    const newMessages = response.data.messages.filter(
      (msg) => !messages.value.some((existingMsg) => existingMsg.id === msg.id)
    );

    if (newMessages.length > 0) {
      messages.value.push(...newMessages); // Add to reactive array.
    }

    isAgentRequested.value = response.data.chat.status !== 'open';
  } catch (error) {
    console.error('Error fetching messages:', error);
    if (error.response && error.response.status === 404) {
        // If session is expired on server, clear local state.
        sessionStorage.removeItem('chat_session_id');
        sessionId.value = null;
    }
  }
};

// Send message to backend
const sendMessage = async () => {
  if (!newMessage.value.trim() || !sessionId.value) return;

  // Optimistic UI update! Show message immediately before server confirmation.
  const tempId = `temp_${Date.now()}`;
  const optimisticMessage = {
    id: tempId,
    content: newMessage.value,
    sender_id: null,
    type: 'text',
  };
  messages.value.push(optimisticMessage); // Immediate visual feedback for user.

  const messageToSend = newMessage.value;
  newMessage.value = ''; // Clear input field

  try {
    const response = await api.post(`/chat/${sessionId.value}/message`, {
      content: messageToSend,
    });

    // Ha visszajött a biztos válasz, töröljük a "kamu" idézőjeles üzenetet...
    const index = messages.value.findIndex(m => m.id === tempId);
    if (index > -1) {
        messages.value.splice(index, 1);
    }

    // ...and replace with final server message (includes DB ID and timestamp).
    if (response.data.messages && Array.isArray(response.data.messages)) {
        response.data.messages.forEach(msg => {
            if (!messages.value.find(m => m.id === msg.id)) {
                messages.value.push(msg);
            }
        });
    }
  } catch (error) {
    console.error('Error sending message:', error);
    // If net is down, remove optimistic message.
    const index = messages.value.findIndex(m => m.id === tempId);
    if (index > -1) {
        messages.value.splice(index, 1);
    }
  }
};

// Escalation to human agent
const requestAgent = async () => {
    if (!sessionId.value) return;
    try {
        const response = await api.post(`/chat/${sessionId.value}/escalate`);
        isAgentRequested.value = true;
        
        if (response.data.chat && response.data.chat.messages) {
             await fetchMessages();
        }
    } catch (error) {
        console.error('Error requesting agent:', error);
    }
};

// === REAL-TIME COMMUNICATION (WebSockets / Laravel Echo) ===
// Real-time messages (no reload needed) typed by the agent.
const listenForMessages = () => {
  if (!sessionId.value) return;

  // Leave previous channel to avoid multiple listeners.
  if (window.Echo) {
      window.Echo.leave(`chat.${sessionId.value}`);
  }

  // Subscribe to channel and listen for 'NewChatMessage' event.
  window.Echo.channel(`chat.${sessionId.value}`)
    .listen('NewChatMessage', (event) => {
      // Add only if not already present (avoid duplication with polling).
      if (!messages.value.find(m => m.id === event.message.id)) {
        messages.value.push(event.message);
        scrollToBottom();
      }
    });
};

// Fallback polling every 5 seconds.
const startPolling = () => {
  if (pollingInterval) clearInterval(pollingInterval);
  pollingInterval = setInterval(fetchMessages, 5000); 
};

// === LIFECYCLE HOOKS ===

// 'onMounted': Runs as soon as the component is rendered.
onMounted(async () => {
  // Restore open session if available
  if (sessionId.value) {
    await fetchMessages();
    listenForMessages();
    startPolling();
  }
});

// 'onUnmounted': Runs when component is destroyed.
onUnmounted(() => {
  // Clean up to prevent memory leaks.
  if (pollingInterval) clearInterval(pollingInterval);
});
</script>

<style scoped>
/* This section contains all the styling for the chat widget, including responsive adjustments. */
.chat-widget {
  position: fixed;
  bottom: 20px;
  right: 20px;
  z-index: 1000;
}
.chat-bubble {
  width: 60px;
  height: 60px;
  background-color: #0d6efd;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  color: white;
  cursor: pointer;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.25);
  transition: transform 0.2s ease-in-out;
}
.chat-bubble:hover {
  transform: scale(1.1);
}
.chat-icon { width: 32px; height: 32px; }
.chat-window {
  width: 370px;
  height: 550px;
  background: white;
  border-radius: 15px;
  box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2);
  display: flex;
  flex-direction: column;
  overflow: hidden;
  transition: all 0.3s ease-in-out;
}
.chat-header {
  padding: 15px 20px;
  background: #0d6efd;
  color: white;
  font-weight: bold;
  display: flex;
  justify-content: space-between;
  align-items: center;
  flex-shrink: 0;
}
.chat-header button {
  background: none;
  border: none;
  color: white;
  font-size: 1.5rem;
  cursor: pointer;
  line-height: 1;
}
.chat-messages {
  flex-grow: 1;
  padding: 15px;
  overflow-y: auto;
  display: flex;
  flex-direction: column;
  gap: 12px;
  background-color: #f4f7f9;
}
.message { display: flex; max-width: 85%; }
.message-content { padding: 12px 18px; border-radius: 18px; }
.message-content p { margin: 0; padding: 0; word-wrap: break-word; white-space: pre-wrap; }
.message-content small { display: block; font-size: 0.7rem; color: #6c757d; margin-top: 5px; }
.message.is-agent { align-self: flex-start; }
.message.is-agent .message-content { background: #e9ecef; color: #212529; }
.message:not(.is-agent) { align-self: flex-end; }
.message:not(.is-agent) .message-content { background-color: #0d6efd; color: white; }
.message:has([v-text]:empty) { display: none; } /* Hide empty messages if any */
.message:not(.is-agent) .message-content:has(small:contains('Bot')) { background-color: #e2e8f0; color: #1e293b; } /* Bot style hook - simple css workaround or add class in template */
.message:not(.is-agent) .message-content small { color: #f0f0f0; }
.message-content[type="system"] { font-style: italic; color: #6c757d; background: transparent; text-align: center; width: 100%; }
.chat-input {
  padding: 10px 15px;
  border-top: 1px solid #eee;
  background: #fff;
  flex-shrink: 0;
}
.chat-input form { display: flex; align-items: center; gap: 10px; }
.chat-input input {
  flex-grow: 1;
  border: 1px solid #ccc;
  border-radius: 20px;
  padding: 12px 18px;
  font-size: 0.95rem;
  transition: border-color 0.2s;
  outline: none;
}
.chat-input input:focus { border-color: #0d6efd; }
.chat-input button {
  background: #0d6efd;
  color: white;
  border: none;
  border-radius: 50%;
  width: 44px;
  height: 44px;
  flex-shrink: 0;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: background-color 0.2s;
}
.chat-input button:hover:not(:disabled) { background-color: #0b5ed7; }
.chat-input button:disabled { background: #a0a0a0; cursor: not-allowed; }

/* Responsive styles for mobile devices (screens smaller than 480px). */
@media (max-width: 480px) {
  .chat-widget {
    right: 15px;
    bottom: 15px;
  }
  .chat-window {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    width: 100%;
    height: 100%;
    border-radius: 0;
    box-shadow: none;
  }
}
</style>
