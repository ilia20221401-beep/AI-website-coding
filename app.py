import os
import json
from flask import Flask, request, jsonify, send_from_directory
from flask_cors import CORS
from openai import OpenAI
from dotenv import load_dotenv

# Load environment variables from .env
load_dotenv()

app = Flask(__name__, static_folder='.')  # serve static files from current dir
CORS(app)  # allow cross-origin requests from your frontend

# Initialize DeepSeek client (OpenAI-compatible)
client = OpenAI(
    api_key=os.getenv("DEEPSEEK_API_KEY"),
    base_url="https://api.deepseek.com/v1"   # DeepSeek endpoint
)

# System prompt – define the AI's behaviour
SYSTEM_PROMPT = (
    "You are a helpful, friendly AI assistant. "
    "Answer clearly and concisely. Use markdown for code blocks if needed."
)

@app.route('/')
def index():
    """Serve the main HTML page."""
    return send_from_directory('.', 'index.html')  # assuming your HTML is named index.html

@app.route('/api/chat', methods=['POST'])
def chat():
    """
    Accept a user message, send it to DeepSeek, and return the reply.
    Expected JSON: { "message": "User text" }
    """
    try:
        data = request.get_json()
        if not data or 'message' not in data:
            return jsonify({'error': 'Missing "message" field'}), 400

        user_message = data['message'].strip()
        if not user_message:
            return jsonify({'error': 'Message cannot be empty'}), 400

        # Call DeepSeek API
        response = client.chat.completions.create(
            model="deepseek-chat",          # or "deepseek-coder" for code tasks
            messages=[
                {"role": "system", "content": SYSTEM_PROMPT},
                {"role": "user", "content": user_message}
            ],
            temperature=0.7,
            max_tokens=1024,
            stream=False
        )

        reply = response.choices[0].message.content
        return jsonify({'reply': reply})

    except Exception as e:
        # Log the error for debugging (optional)
        app.logger.error(f"DeepSeek API error: {str(e)}")
        return jsonify({'error': f'AI service error: {str(e)}'}), 500

# Optional: serve other static files (CSS, JS) if needed
@app.route('/<path:path>')
def static_files(path):
    return send_from_directory('.', path)

if __name__ == '__main__':
    app.run(debug=True, host='0.0.0.0', port=5000)
