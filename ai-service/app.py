"""
StaySmart AI - AI Microservice with Conversational Intelligence
===============================================================
"""

import re
import numpy as np
import pandas as pd
from flask import Flask, request, jsonify
from flask_cors import CORS
from sklearn.linear_model import LinearRegression
from sklearn.preprocessing import OneHotEncoder
from sklearn.pipeline import Pipeline
from sklearn.compose import ColumnTransformer

app = Flask(__name__)
CORS(app)

# ----------------------------------------------------------------------
# CONVERSATION STATE - Remembers what user asked
# ----------------------------------------------------------------------
conversation_memory = {}

# ----------------------------------------------------------------------
# AI Rental Price Prediction Model
# ----------------------------------------------------------------------
def build_price_model():
    rng = np.random.default_rng(42)
    n = 300
    cities = rng.choice(["Islamabad", "Lahore", "Karachi", "Rawalpindi"], n, p=[0.35, 0.3, 0.2, 0.15])
    rooms = rng.integers(1, 6, n)
    types = rng.choice(["apartment", "house", "studio", "room", "hostel"], n)

    city_base = {"Islamabad": 28000, "Lahore": 26000, "Karachi": 25000, "Rawalpindi": 22000}
    type_mult = {"apartment": 1.0, "house": 1.35, "studio": 0.75, "room": 0.5, "hostel": 0.4}

    n_facilities = rng.integers(0, 6, n)
    base = np.array([city_base[c] for c in cities])
    mult = np.array([type_mult[t] for t in types])
    price = (base + rooms * 7000) * mult + n_facilities * 1200
    price += rng.normal(0, 2000, n)
    price = np.clip(price, 8000, None)

    df = pd.DataFrame({
        "city": cities,
        "rooms": rooms,
        "property_type": types,
        "n_facilities": n_facilities,
        "rent": price,
    })

    X = df[["city", "rooms", "property_type", "n_facilities"]]
    y = df["rent"]

    preprocessor = ColumnTransformer(transformers=[
        ("cat", OneHotEncoder(handle_unknown="ignore"), ["city", "property_type"]),
    ], remainder="passthrough")

    model = Pipeline(steps=[
        ("preprocess", preprocessor),
        ("regressor", LinearRegression()),
    ])

    model.fit(X, y)
    return model

PRICE_MODEL = build_price_model()

# ----------------------------------------------------------------------
# CONVERSATIONAL AI CHATBOT
# ----------------------------------------------------------------------

# Helper: Extract information from user message
def extract_info(message):
    msg = message.lower()
    
    # Extract budget
    budget_match = re.search(r'(\d{3,6})', msg)
    budget = int(budget_match.group(1)) if budget_match else None
    
    # Extract city
    cities = ['islamabad', 'lahore', 'karachi', 'rawalpindi', 'peshawar', 'multan']
    city = None
    for c in cities:
        if c in msg:
            city = c.title()
            break
    
    # Extract lifestyle
    lifestyle_keywords = {
        'quiet': ['quiet', 'peaceful', 'calm', 'study', 'silent'],
        'social': ['social', 'party', 'friends', 'loud'],
        'family': ['family', 'kids', 'children', 'school'],
        'professional': ['professional', 'work', 'office', 'corporate'],
        'student': ['student', 'university', 'college', 'campus']
    }
    lifestyle = None
    for key, values in lifestyle_keywords.items():
        if any(v in msg for v in values):
            lifestyle = key.title()
            break
    
    # Extract property type
    property_types = ['apartment', 'house', 'studio', 'penthouse', 'room']
    property_type = None
    for p in property_types:
        if p in msg:
            property_type = p.title()
            break
    
    return {
        'budget': budget,
        'city': city,
        'lifestyle': lifestyle,
        'property_type': property_type,
        'has_info': budget or city or lifestyle or property_type
    }

# Helper: Check if message is a greeting
def is_greeting(message):
    greetings = ['hi', 'hello', 'hey', 'good morning', 'good evening', 'salam', 'assalam', 'howdy', 'yo']
    msg = message.lower().strip()
    return msg in greetings or msg.startswith(('hi', 'hello', 'hey'))

# Helper: Check if message is asking for help
def is_help(message):
    help_words = ['help', 'what can you do', 'how to use', 'guide', 'tutorial', 'explain']
    msg = message.lower()
    return any(w in msg for w in help_words)

# Helper: Check if user is saying thank you
def is_thanks(message):
    thanks = ['thank', 'thanks', 'ty', 'thank you', 'appreciate', 'grateful']
    msg = message.lower()
    return any(w in msg for w in thanks)

# ===== MAIN CHATBOT RESPONSE =====
@app.route("/chatbot", methods=["POST"])
def chatbot():
    data = request.get_json(force=True) or {}
    query = data.get("query", "")
    properties = data.get("properties", [])
    user_id = data.get("user_id", "default")
    
    # Get or create conversation state
    if user_id not in conversation_memory:
        conversation_memory[user_id] = {
            'asked_budget': False,
            'asked_city': False,
            'asked_lifestyle': False,
            'last_response': None
        }
    
    state = conversation_memory[user_id]
    
    # --- CHECK FOR GREETING ---
    if is_greeting(query):
        return jsonify({
            "reply": "👋 Hello! Welcome to StaySmart AI!\n\nI can help you find the perfect property. Please tell me:\n\n1️⃣ What is your monthly budget? (e.g., 50000)\n2️⃣ Which city do you prefer? (Islamabad/Lahore/Karachi)\n3️⃣ What lifestyle are you looking for? (quiet/social/family)\n\nExample: 'I want a quiet apartment in Islamabad under 50000'",
            "properties": [],
            "source": "conversational-ai"
        })
    
    # --- CHECK FOR HELP ---
    if is_help(query):
        return jsonify({
            "reply": "📖 Here's how I can help you:\n\n1. 🏠 Find properties - Tell me your budget, city, and lifestyle\n2. 💰 Budget advice - Ask about rental prices in different cities\n3. 🤝 Roommate matching - Find compatible roommates\n4. 📊 Expense calculator - Estimate monthly costs\n\nExample: 'Find a quiet apartment in Islamabad under 50000'",
            "properties": [],
            "source": "conversational-ai"
        })
    
    # --- CHECK FOR THANKS ---
    if is_thanks(query):
        return jsonify({
            "reply": "🙌 You're welcome! I'm always here to help.\n\nIs there anything else you'd like to know? You can ask about properties, budgets, or roommate matching!",
            "properties": [],
            "source": "conversational-ai"
        })
    
    # --- EXTRACT INFORMATION ---
    info = extract_info(query)
    
    # --- CONVERSATIONAL FLOW ---
    
    # Case 1: User provided ALL information
    if info['budget'] and info['city'] and info['lifestyle']:
        # Search with all filters
        filtered = []
        for p in properties:
            match = True
            if info['city'] and p.get('city', '').lower() != info['city'].lower():
                match = False
            if info['budget'] and float(p.get('rent', 0)) > info['budget']:
                match = False
            if info['lifestyle'] and p.get('lifestyle_tag', '').lower() != info['lifestyle'].lower():
                match = False
            if info['property_type'] and p.get('property_type', '').lower() != info['property_type'].lower():
                match = False
            if match:
                filtered.append(p)
        
        # Reset state
        conversation_memory[user_id] = {
            'asked_budget': False,
            'asked_city': False,
            'asked_lifestyle': False,
            'last_response': None
        }
        
        if filtered:
            reply = f"🏠 I found {len(filtered)} properties matching your criteria:\n\n"
            for i, p in enumerate(filtered[:5]):
                match_score = calculate_match_score(p, info)
                reply += f"{i+1}. **{p.get('property_name', 'Property')}**\n"
                reply += f"   📍 {p.get('city', 'Unknown')} | Rs. {p.get('rent', 0):,}/month\n"
                reply += f"   🛏️ {p.get('rooms', 1)} bed | {p.get('property_type', 'Apartment')}\n"
                reply += f"   🤖 AI Match: {match_score}%\n\n"
            reply += "💡 Click on any property to view details and book!"
        else:
            reply = f"😕 I couldn't find properties matching all your criteria.\n\n"
            reply += f"📊 Based on your budget of Rs. {info['budget']:,} in {info['city']}, here are some nearby options:\n\n"
            nearby = [p for p in properties if p.get('city', '').lower() == info['city'].lower() and float(p.get('rent', 0)) <= info['budget'] + 10000]
            if nearby:
                for i, p in enumerate(nearby[:3]):
                    reply += f"{i+1}. {p.get('property_name')} - Rs. {p.get('rent', 0):,}/month\n"
                reply += f"\n💡 Try increasing your budget or changing your lifestyle preference."
            else:
                reply += f"💡 Try increasing your budget or choosing a different city."
        
        return jsonify({
            "reply": reply,
            "properties": filtered[:5],
            "source": "conversational-ai"
        })
    
    # Case 2: User provided budget + city, missing lifestyle
    elif info['budget'] and info['city'] and not info['lifestyle']:
        # Filter by budget + city
        filtered = [p for p in properties if p.get('city', '').lower() == info['city'].lower() and float(p.get('rent', 0)) <= info['budget']]
        
        reply = f"🏠 I found {len(filtered)} properties in {info['city']} under Rs. {info['budget']:,}.\n\n"
        
        # Ask clarifying question
        reply += "🤔 What lifestyle are you looking for?\n\n"
        reply += "Choose from:\n"
        reply += "• 🧘 Quiet / Study focused\n"
        reply += "• 🎉 Social / Party\n"
        reply += "• 👨‍👩‍👧‍👦 Family oriented\n"
        reply += "• 💼 Professional / Work\n\n"
        reply += "Example: 'I prefer a quiet environment'"
        
        # Show some options
        if filtered:
            reply += "\n\n📊 Here are some options I found:\n"
            for p in filtered[:3]:
                reply += f"• {p.get('property_name')} - Rs. {p.get('rent', 0):,}/month\n"
        
        # Store state
        conversation_memory[user_id] = {
            'asked_budget': True,
            'asked_city': True,
            'asked_lifestyle': False,
            'last_response': 'needs_lifestyle'
        }
        
        return jsonify({
            "reply": reply,
            "properties": filtered[:5],
            "source": "conversational-ai"
        })
    
    # Case 3: User provided budget only
    elif info['budget'] and not info['city']:
        filtered = [p for p in properties if float(p.get('rent', 0)) <= info['budget']]
        
        reply = f"💰 I found {len(filtered)} properties under Rs. {info['budget']:,}.\n\n"
        reply += "📍 Which city are you looking for?\n\n"
        reply += "Options: Islamabad, Lahore, Karachi, Rawalpindi, Peshawar\n\n"
        
        if filtered:
            reply += "📊 Top matches in your budget:\n"
            for p in filtered[:3]:
                reply += f"• {p.get('property_name')} - Rs. {p.get('rent', 0):,}/month in {p.get('city', 'Unknown')}\n"
        
        conversation_memory[user_id] = {
            'asked_budget': True,
            'asked_city': False,
            'asked_lifestyle': False,
            'last_response': 'needs_city'
        }
        
        return jsonify({
            "reply": reply,
            "properties": filtered[:5],
            "source": "conversational-ai"
        })
    
    # Case 4: User provided city only
    elif info['city'] and not info['budget']:
        filtered = [p for p in properties if p.get('city', '').lower() == info['city'].lower()]
        
        reply = f"📍 I found {len(filtered)} properties in {info['city']}.\n\n"
        reply += "💰 What is your monthly budget?\n\n"
        reply += "Example: '50000' or 'under 60000'"
        
        if filtered:
            reply += "\n\n📊 Properties in {info['city']}:\n"
            for p in filtered[:3]:
                reply += f"• {p.get('property_name')} - Rs. {p.get('rent', 0):,}/month\n"
        
        conversation_memory[user_id] = {
            'asked_budget': False,
            'asked_city': True,
            'asked_lifestyle': False,
            'last_response': 'needs_budget'
        }
        
        return jsonify({
            "reply": reply,
            "properties": filtered[:5],
            "source": "conversational-ai"
        })
    
    # Case 5: User provided lifestyle only
    elif info['lifestyle'] and not info['budget'] and not info['city']:
        filtered = [p for p in properties if p.get('lifestyle_tag', '').lower() == info['lifestyle'].lower()]
        
        reply = f"🧘 I found {len(filtered)} {info['lifestyle']} lifestyle properties.\n\n"
        reply += "📍 Which city are you looking for?\n"
        reply += "💰 What is your monthly budget?\n\n"
        reply += "Example: 'Islamabad under 50000'"
        
        if filtered:
            reply += "\n📊 Top {info['lifestyle']} matches:\n"
            for p in filtered[:3]:
                reply += f"• {p.get('property_name')} - Rs. {p.get('rent', 0):,}/month in {p.get('city', 'Unknown')}\n"
        
        conversation_memory[user_id] = {
            'asked_budget': False,
            'asked_city': False,
            'asked_lifestyle': True,
            'last_response': 'needs_more_info'
        }
        
        return jsonify({
            "reply": reply,
            "properties": filtered[:5],
            "source": "conversational-ai"
        })
    
    # Case 6: No information provided
    else:
        reply = "🤖 I'm here to help you find the perfect property!\n\n"
        reply += "Please tell me:\n"
        reply += "1️⃣ Your budget (e.g., '50000')\n"
        reply += "2️⃣ Preferred city (Islamabad/Lahore/Karachi)\n"
        reply += "3️⃣ Lifestyle (quiet/social/family)\n\n"
        reply += "📝 Example: 'Find a quiet apartment in Islamabad under 50000'\n\n"
        reply += "Or type 'help' for more guidance."
        
        return jsonify({
            "reply": reply,
            "properties": [],
            "source": "conversational-ai"
        })

# Helper: Calculate match score
def calculate_match_score(property, info):
    score = 70
    
    if info['budget'] and float(property.get('rent', 0)) <= info['budget']:
        score += 15
    if info['city'] and property.get('city', '').lower() == info['city'].lower():
        score += 10
    if info['lifestyle'] and property.get('lifestyle_tag', '').lower() == info['lifestyle'].lower():
        score += 5
    
    return min(99, score)

# ===== OTHER ENDPOINTS =====
@app.route("/predict-price", methods=["POST"])
def predict_price():
    data = request.get_json(force=True) or {}
    city = (data.get("city") or "Islamabad").title()
    rooms = int(data.get("rooms", 1))
    property_type = (data.get("property_type") or "apartment").lower()
    facilities = data.get("facilities", [])
    n_facilities = len(facilities) if isinstance(facilities, list) else 0

    X = pd.DataFrame([{
        "city": city,
        "rooms": rooms,
        "property_type": property_type,
        "n_facilities": n_facilities,
    }])

    predicted = float(PRICE_MODEL.predict(X)[0])
    predicted = max(predicted, 5000)
    predicted = round(predicted / 500) * 500

    return jsonify({
        "predicted_price": predicted,
        "currency": "PKR",
        "input": {
            "city": city,
            "rooms": rooms,
            "property_type": property_type,
            "facilities_count": n_facilities,
        },
        "model": "LinearRegression (trained on synthetic historical data)",
    })

@app.route("/recommend", methods=["POST"])
def recommend():
    data = request.get_json(force=True) or {}
    budget = float(data.get("budget", 0))
    location = (data.get("location") or "").lower().strip()
    lifestyle = (data.get("lifestyle") or "").lower().strip()
    properties = data.get("properties", [])

    results = []
    for p in properties:
        score = 0.0
        rent = float(p.get("rent", 0))
        if rent <= budget:
            score += 50
        elif budget > 0:
            over_ratio = (rent - budget) / budget
            score += max(0, 50 * (1 - over_ratio))
        if location and location in (p.get("city", "").lower()):
            score += 10
        if lifestyle and lifestyle == (p.get("lifestyle_tag") or "").lower():
            score += 25
        results.append({
            "property_id": p.get("property_id"),
            "property_name": p.get("property_name"),
            "city": p.get("city"),
            "rent": rent,
            "rooms": p.get("rooms"),
            "facilities": p.get("facilities"),
            "images": p.get("images"),
            "match_score": round(min(score, 100)),
        })

    results.sort(key=lambda r: r["match_score"], reverse=True)
    return jsonify({"recommendations": results[:10]})

@app.route("/", methods=["GET"])
def health():
    return jsonify({"status": "ok", "service": "StaySmart AI - Conversational AI Microservice"})

if __name__ == "__main__":
    app.run(host="127.0.0.1", port=5000, debug=True)