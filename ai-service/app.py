from flask import Flask, request, jsonify
from flask_cors import CORS

app = Flask(__name__)
CORS(app)

@app.route('/chatbot', methods=['POST'])
def chatbot():
    data = request.json or {}
    query = data.get('query', 'Hello')
    
    return jsonify({
        'reply': f'You said: "{query}". I am your AI assistant!',
        'status': 'ok'
    })

@app.route('/')
def home():
    return jsonify({'status': 'ok', 'message': 'AI Service is running!'})

if __name__ == '__main__':
    app.run(host='0.0.0.0', port=10000)
