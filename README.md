# StaySmart AI - Intelligent Long-Term House Rental & Living Platform

A complete, working implementation of the **StaySmart AI** project proposal (CS-309 Web Design & Development).
This is a full-stack web application with:

- **Frontend**: HTML5, CSS3, JavaScript, Bootstrap 5
- **Backend**: PHP (REST-style API, session-based auth, PDO/MySQL)
- **Database**: MySQL
- **AI Microservice**: Python + Flask + Scikit-Learn + Pandas

---

## 1. Project Structure

```
staysmart-ai/
├── database/
│   └── schema.sql              # MySQL schema + seed data
├── backend/                     # PHP backend (place inside your web server root)
│   ├── config/
│   │   └── db.php               # DB connection settings (edit this!)
│   ├── includes/
│   │   └── bootstrap.php        # Shared helpers (CORS, session, JSON)
│   ├── auth/
│   │   ├── register.php
│   │   ├── login.php
│   │   ├── logout.php
│   │   └── me.php
│   └── api/
│       ├── properties.php       # Search, add, edit, delete properties
│       ├── bookings.php         # Create/manage bookings (with overlap check)
│       ├── reviews.php          # Property reviews
│       ├── roommates.php        # Roommate matching
│       ├── recommend.php        # AI property recommendation
│       ├── price-predict.php    # AI rental price prediction
│       ├── chatbot.php           # AI virtual assistant
│       ├── expense.php          # Living expense calculator
│       └── admin.php            # Admin: users, properties, bookings, reports
├── ai-service/                  # Python Flask AI microservice
│   ├── app.py
│   └── requirements.txt
└── frontend/
    ├── css/style.css
    ├── js/main.js
    └── pages/
        ├── index.html
        ├── login.html
        ├── register.html
        ├── properties.html
        ├── property-details.html
        ├── recommend.html
        ├── roommate.html
        ├── expense-calculator.html
        ├── tenant-dashboard.html
        ├── owner-dashboard.html
        └── admin-dashboard.html
```

---

## 2. Prerequisites

- **XAMPP / WAMP / MAMP** (or any PHP 8+ server with MySQL) - for the backend
- **Python 3.10+** - for the AI microservice
- A modern browser (Chrome/Firefox/Edge)

---

## 3. Database Setup

1. Start MySQL (via XAMPP/phpMyAdmin or `mysql` CLI).
2. Import the schema and seed data:

```bash
mysql -u root -p < database/schema.sql
```

This creates the `staysmart_ai` database with tables: `users`, `properties`, `bookings`, `reviews`, `roommate_requests`, and inserts demo accounts + 5 sample properties.

**Demo accounts (password for all: `password123`)**

| Role   | Email                       |
|--------|-----------------------------|
| Admin  | admin@staysmart.ai          |
| Owner  | ali.owner@staysmart.ai      |
| Tenant | sara.tenant@staysmart.ai    |
| Tenant | bilal.tenant@staysmart.ai   |

---

## 4. Backend Setup (PHP)

1. Copy the `backend/` folder into your web server's document root, e.g.:
   - XAMPP: `C:\xampp\htdocs\staysmart-ai\backend`
   - Linux/MAMP: `/var/www/html/staysmart-ai/backend`

2. Edit `backend/config/db.php` and set your MySQL username/password:

```php
$DB_HOST = "127.0.0.1";
$DB_NAME = "staysmart_ai";
$DB_USER = "root";
$DB_PASS = "";   // your MySQL password
```

3. Start Apache + MySQL (via XAMPP control panel or `sudo service apache2 start`).

4. Test it: open `http://localhost/staysmart-ai/backend/auth/me.php` in your browser.
   You should see `{"logged_in":false}`.

---

## 5. AI Microservice Setup (Python/Flask)

The AI microservice powers:
- AI Smart Property Recommendation
- AI Rental Price Prediction (Linear Regression trained on synthetic data)
- Roommate Matching (compatibility scoring)
- AI Virtual Assistant (chatbot)

> **Note:** The PHP API endpoints (`recommend.php`, `price-predict.php`, `roommates.php`, `chatbot.php`) all have **built-in fallback logic**, so the website works even if you don't run the Flask service. Running it gives you the full ML-based experience (especially the trained price-prediction model).

```bash
cd ai-service
python -m venv venv
source venv/bin/activate          # Windows: venv\Scripts\activate
pip install -r requirements.txt
python app.py
```

The service runs at `http://127.0.0.1:5000`. Keep this terminal open while using the site.

---

## 6. Frontend Setup

1. Copy the `frontend/` folder next to `backend/` so you have:
   `htdocs/staysmart-ai/frontend/...` and `htdocs/staysmart-ai/backend/...`

2. **Important:** Open `frontend/js/main.js` and confirm `API_BASE` matches where you placed the backend:

```js
const API_BASE = "http://localhost/staysmart-ai/backend";
```

3. Open the site in your browser:

```
http://localhost/staysmart-ai/frontend/pages/index.html
```

(Serve the frontend through the same Apache server so cookies/sessions work correctly - don't open the HTML files directly via `file://`.)

---

## 7. Feature Walkthrough (maps to the proposal)

| Proposal Feature | Where it lives |
|---|---|
| Long-Term Booking System (monthly/quarterly/semester/annual) | `property-details.html` booking form -> `api/bookings.php` (with date-overlap validation) |
| AI Smart Property Recommendation | `recommend.html` -> `api/recommend.php` -> `ai-service/app.py` `/recommend` |
| Roommate Matching System | `roommate.html` -> `api/roommates.php` -> `ai-service/app.py` `/match-roommates` |
| AI Rental Price Prediction | `owner-dashboard.html` -> `api/price-predict.php` -> `ai-service/app.py` `/predict-price` (Linear Regression) |
| Living Expense Calculator | `expense-calculator.html` -> `api/expense.php` |
| AI Virtual Assistant (chatbot) | Floating chat widget on every page -> `api/chatbot.php` -> `ai-service/app.py` `/chatbot` |
| User Module (register/login/profile/search/booking) | `register.html`, `login.html`, `tenant-dashboard.html`, `properties.html` |
| Property Owner Module | `owner-dashboard.html` (add/edit property, manage bookings, AI price prediction) |
| Admin Module | `admin-dashboard.html` (manage users, approve/moderate properties, view bookings, reports) |
| Review and Rating System | `property-details.html` (reviews list + submission form) |

---

## 8. Roles & Access

- **Tenant** (default on registration): search/book properties, roommate matching, reviews, expense calculator, tenant dashboard.
- **Owner**: everything a tenant can do, plus add/edit properties, AI price prediction, manage bookings on their properties.
- **Admin**: full access - approve/reject property listings, manage users and roles, view all bookings and platform-wide reports/stats.

To make a user an admin, either register normally and change their `role` to `admin` directly in the database, or log in as the seeded `admin@staysmart.ai` account and promote users from the Admin Dashboard's Users tab.

---

## 9. Notes on the AI Models

- **Price Prediction**: A `scikit-learn` `LinearRegression` model is trained at startup on synthetic historical data (city, rooms, property type, facility count -> rent), mimicking the "based on historical data" requirement from the proposal. Replace `build_price_model()` in `ai-service/app.py` with real historical booking data for production use.
- **Recommendation Engine**: Weighted scoring (budget fit 50%, location 10%, lifestyle 25%, facilities 15%) producing the 0-100% "compatibility score" shown in the proposal's example (House A - 95% Match, etc.).
- **Roommate Matching**: Weighted scoring (budget similarity 40%, lifestyle 25%, city 20%, institution 10%, occupation 5%).
- **Chatbot**: Rule-based NLU using regex/keyword extraction for budget, city, property type, lifestyle and duration - matches the example queries in the proposal ("Show houses under Rs. 50,000", "Recommend houses for a six-month stay").

---

## 10. Future Enhancements (from proposal, not yet implemented)

- Online Payment Integration
- Interactive Maps
- Mobile Application
- Real-Time Chat System
- Smart Contract-Based Rental Agreements
- Advanced AI Recommendation Models (e.g., collaborative filtering, embeddings)
