import os
from flask import Flask, request, jsonify
from openai import OpenAI

app = Flask(__name__)

# Initialize the client – it will automatically read OPENAI_API_KEY from env
client = OpenAI(
    base_url="https://api.bluesminds.com"   # key is read from environment
)

@app.route('/api/chat', methods=['POST'])
def chat():
    data = request.get_json()
    user_message = data.get('message')
    # ... rest of your code
