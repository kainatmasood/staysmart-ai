// Send message to AI chatbot
async function sendChatMessage() {
    const input = document.getElementById('chatInput');
    const message = input.value.trim();
    
    if (!message) return;
    
    // Add user message to chat
    addChatMessage(message, 'user');
    input.value = '';
    
    // Show typing indicator
    addTypingIndicator();
    
    try {
        const response = await fetch('http://localhost:5000/api/ai/chatbot', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ message: message })
        });
        
        const data = await response.json();
        
        // Remove typing indicator
        removeTypingIndicator();
        
        if (data.status) {
            addChatMessage(data.response, 'ai');
            
            // Parse action commands
            if (message.toLowerCase().includes('find property') || 
                message.toLowerCase().includes('search property')) {
                setTimeout(() => {
                    addChatMessage("I'll help you search for properties! Please use the search form above or tell me your budget and location.", 'ai');
                }, 500);
            }
        } else {
            addChatMessage("Sorry, I'm having trouble connecting. Please try again.", 'ai');
        }
    } catch (error) {
        console.error('Chatbot error:', error);
        removeTypingIndicator();
        addChatMessage("Sorry, I'm temporarily unavailable. Please use the search form above.", 'ai');
    }
}

// Add message to chat
function addChatMessage(message, sender) {
    const chatContainer = document.getElementById('chatMessages');
    const messageDiv = document.createElement('div');
    messageDiv.className = `chat-message ${sender === 'user' ? 'user-message' : 'ai-message'}`;
    messageDiv.innerHTML = sender === 'user' ? 
        `<i class="fas fa-user"></i> ${message}` :
        `<i class="fas fa-robot"></i> ${message}`;
    chatContainer.appendChild(messageDiv);
    chatContainer.scrollTop = chatContainer.scrollHeight;
}

// Add typing indicator
function addTypingIndicator() {
    const chatContainer = document.getElementById('chatMessages');
    const typingDiv = document.createElement('div');
    typingDiv.className = 'chat-message ai-message';
    typingDiv.id = 'typingIndicator';
    typingDiv.innerHTML = '<i class="fas fa-robot"></i> <span class="typing-dots">...</span>';
    chatContainer.appendChild(typingDiv);
    chatContainer.scrollTop = chatContainer.scrollHeight;
}

// Remove typing indicator
function removeTypingIndicator() {
    const typingIndicator = document.getElementById('typingIndicator');
    if (typingIndicator) {
        typingIndicator.remove();
    }
}

// Enter key to send message
document.getElementById('chatInput')?.addEventListener('keypress', function(e) {
    if (e.key === 'Enter') {
        sendChatMessage();
    }
});

// Add CSS for typing animation
const style = document.createElement('style');
style.textContent = `
    .typing-dots {
        display: inline-block;
        animation: typing 1.5s infinite;
    }
    @keyframes typing {
        0%, 100% { opacity: 0.3; content: '.'; }
        50% { opacity: 1; content: '..'; }
    }
`;
document.head.appendChild(style);