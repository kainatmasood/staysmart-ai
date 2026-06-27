/* ============================================================
   StaySmart AI - Shared Frontend Logic
   ============================================================ */

// Change this if your backend is hosted elsewhere
const API_BASE = "http://localhost/staysmart-ai/backend";

/** Generic fetch wrapper that sends/receives JSON and includes session cookies */
async function api(path, { method = "GET", body = null } = {}) {
  const opts = {
    method,
    credentials: "include", // send PHP session cookie
    headers: { "Content-Type": "application/json" },
  };
  if (body) opts.body = JSON.stringify(body);

  const res = await fetch(`${API_BASE}${path}`, opts);
  let data;
  try {
    data = await res.json();
  } catch (e) {
    data = {};
  }
  if (!res.ok) {
    throw new Error(data.error || `Request failed (${res.status})`);
  }
  return data;
}

/** Show a dismissible alert inside a container element */
function showAlert(containerId, message, type = "danger") {
  const el = document.getElementById(containerId);
  if (!el) return;
  el.innerHTML = `<div class="alert alert-${type} alert-dismissible fade show" role="alert">
      ${message}
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>`;
}

/** Update navbar based on login state. Call on every page load. */
async function refreshAuthNav() {
  const navAuth = document.getElementById("nav-auth");
  if (!navAuth) return;

  try {
    const data = await api("/auth/me.php");
    if (data.logged_in) {
      let dashboardLink = "tenant-dashboard.html";
      if (data.user.role === "owner") dashboardLink = "owner-dashboard.html";
      if (data.user.role === "admin") dashboardLink = "admin-dashboard.html";

      navAuth.innerHTML = `
        <li class="nav-item"><span class="nav-link">Hi, ${data.user.name}</span></li>
        <li class="nav-item"><a class="nav-link" href="${dashboardLink}">Dashboard</a></li>
        <li class="nav-item"><a class="nav-link" href="#" id="logout-link">Logout</a></li>
      `;
      document.getElementById("logout-link").addEventListener("click", async (e) => {
        e.preventDefault();
        await api("/auth/logout.php", { method: "POST" });
        window.location.href = "index.html";
      });
    } else {
      navAuth.innerHTML = `
        <li class="nav-item"><a class="nav-link" href="login.html">Login</a></li>
        <li class="nav-item"><a class="nav-link" href="register.html">Register</a></li>
      `;
    }
  } catch (e) {
    // Backend not reachable - leave default nav
  }
}

/** Returns the currently logged-in user, or null */
async function getCurrentUser() {
  try {
    const data = await api("/auth/me.php");
    return data.logged_in ? data.user : null;
  } catch (e) {
    return null;
  }
}

/* ============================================================
   Chatbot Widget (injected on every page)
   ============================================================ */
function initChatbot() {
  const toggle = document.createElement("button");
  toggle.id = "chatbot-toggle";
  toggle.innerHTML = "💬";
  toggle.title = "Ask StaySmart AI Assistant";

  const win = document.createElement("div");
  win.id = "chatbot-window";
  win.innerHTML = `
    <div id="chatbot-header">StaySmart AI Assistant</div>
    <div id="chatbot-messages">
      <div class="chat-msg bot"><div class="bubble">Hi! Ask me things like "Show houses under Rs. 50,000 in Islamabad" or "Find a property for a 6 month stay".</div></div>
    </div>
    <div id="chatbot-input-group" class="input-group">
      <input type="text" id="chatbot-input" class="form-control" placeholder="Type your question..." />
      <button class="btn btn-primary" id="chatbot-send">Send</button>
    </div>
  `;

  document.body.appendChild(toggle);
  document.body.appendChild(win);

  toggle.addEventListener("click", () => win.classList.toggle("open"));

  const messages = win.querySelector("#chatbot-messages");
  const input = win.querySelector("#chatbot-input");
  const send = win.querySelector("#chatbot-send");

  function addMessage(text, who = "bot") {
    const div = document.createElement("div");
    div.className = `chat-msg ${who}`;
    div.innerHTML = `<div class="bubble">${text}</div>`;
    messages.appendChild(div);
    messages.scrollTop = messages.scrollHeight;
  }

  async function sendQuery() {
    const q = input.value.trim();
    if (!q) return;
    addMessage(q, "user");
    input.value = "";

    try {
      const data = await api("/api/chatbot.php", { method: "POST", body: { query: q } });
      addMessage(data.reply || "Here's what I found:");
      if (data.properties && data.properties.length) {
        let html = "<ul class='mb-0 ps-3'>";
        data.properties.forEach((p) => {
          html += `<li>${p.property_name} - ${p.city} - Rs. ${Number(p.rent).toLocaleString()}</li>`;
        });
        html += "</ul>";
        addMessage(html);
      }
    } catch (e) {
      addMessage("Sorry, I couldn't process that right now.");
    }
  }

  send.addEventListener("click", sendQuery);
  input.addEventListener("keydown", (e) => {
    if (e.key === "Enter") sendQuery();
  });
}

document.addEventListener("DOMContentLoaded", () => {
  refreshAuthNav();
  initChatbot();
});
