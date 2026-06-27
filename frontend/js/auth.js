// Check login status
function checkLoginStatus() {
    const storedUser = localStorage.getItem('staySmartUser');
    if (storedUser) {
        currentUser = JSON.parse(storedUser);
        updateUIForLoggedInUser();
    }
}

// Update UI for logged in user
function updateUIForLoggedInUser() {
    document.getElementById('authNav').style.display = 'none';
    document.getElementById('userNav').style.display = 'block';
    document.getElementById('userName').textContent = currentUser.name.split(' ')[0];
}

// Login handler
document.getElementById('loginForm')?.addEventListener('submit', async function(e) {
    e.preventDefault();
    
    const email = document.getElementById('loginEmail').value;
    const password = document.getElementById('loginPassword').value;
    
    try {
        const response = await fetch('http://localhost/StaySmart-AI/backend/api/login.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ email, password })
        });
        
        const data = await response.json();
        
        if (data.status) {
            currentUser = data.data;
            localStorage.setItem('staySmartUser', JSON.stringify(currentUser));
            updateUIForLoggedInUser();
            
            // Close modal
            const modal = bootstrap.Modal.getInstance(document.getElementById('loginModal'));
            modal.hide();
            
            alert('Login successful!');
            loadProperties(); // Reload properties with user context
        } else {
            alert('Login failed: ' + data.message);
        }
    } catch (error) {
        console.error('Login error:', error);
        alert('Login failed. Please try again.');
    }
});

// Register handler
document.getElementById('registerForm')?.addEventListener('submit', async function(e) {
    e.preventDefault();
    
    const userData = {
        name: document.getElementById('regName').value,
        email: document.getElementById('regEmail').value,
        password: document.getElementById('regPassword').value,
        phone: document.getElementById('regPhone').value,
        role: document.getElementById('regRole').value,
        budget: document.getElementById('regBudget').value || 0,
        gender: 'other',
        occupation: 'student',
        lifestyle: 'balanced'
    };
    
    try {
        const response = await fetch('http://localhost/StaySmart-AI/backend/api/register.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(userData)
        });
        
        const data = await response.json();
        
        if (data.status) {
            alert('Registration successful! Please login.');
            // Switch to login tab
            const loginTab = new bootstrap.Tab(document.querySelector('#authTab button:first-child'));
            loginTab.show();
            
            // Clear form
            document.getElementById('registerForm').reset();
        } else {
            alert('Registration failed: ' + data.message);
        }
    } catch (error) {
        console.error('Registration error:', error);
        alert('Registration failed. Please try again.');
    }
});

// Logout function
function logout() {
    localStorage.removeItem('staySmartUser');
    currentUser = null;
    document.getElementById('authNav').style.display = 'block';
    document.getElementById('userNav').style.display = 'none';
    alert('Logged out successfully!');
    loadProperties();
}