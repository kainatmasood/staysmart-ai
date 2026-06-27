from flask import Flask, request, jsonify
from flask_cors import CORS
import re

app = Flask(__name__)
CORS(app)

@app.route('/chatbot', methods=['POST'])
def chatbot():
    data = request.json or {}
    query = data.get('query', '')
    
    if not query:
        return jsonify({'reply': 'Please type a message!'})
    
    # Simple response
    reply = f"You said: '{query}'. I'm your AI assistant!"
    
    return jsonify({'reply': reply})

@app.route('/')
def home():
    return jsonify({'status': 'ok', 'message': 'StaySmart AI Service is running!'})

if __name__ == '__main__':
    app.run(host='0.0.0.0', port=10000)
