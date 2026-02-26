import os
import sqlite3
from datetime import datetime
from pathlib import Path
from typing import Any

from flask import Flask, abort, g, jsonify, request, send_from_directory, session
from werkzeug.utils import secure_filename

BASE_DIR = Path(__file__).resolve().parent
UPLOAD_DIR = BASE_DIR / "support"
DATABASE_PATH = BASE_DIR / "copytocopy.db"
MAX_CONTENT_LENGTH = 16 * 1024 * 1024  # 16 MB


app = Flask(__name__, static_folder="static", template_folder="templates")
app.config["SECRET_KEY"] = os.environ.get("SECRET_KEY", "change-me-in-production")
app.config["MAX_CONTENT_LENGTH"] = MAX_CONTENT_LENGTH


# Ensure support directory exists.
UPLOAD_DIR.mkdir(parents=True, exist_ok=True)


def get_db() -> sqlite3.Connection:
    if "db" not in g:
        conn = sqlite3.connect(DATABASE_PATH)
        conn.row_factory = sqlite3.Row
        g.db = conn
    return g.db


def init_db() -> None:
    db = get_db()
    db.execute(
        """
        CREATE TABLE IF NOT EXISTS users (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            username TEXT NOT NULL UNIQUE
        )
        """
    )
    db.execute(
        """
        CREATE TABLE IF NOT EXISTS user_files (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER NOT NULL,
            filename TEXT NOT NULL,
            stored_name TEXT NOT NULL UNIQUE,
            filesize INTEGER NOT NULL,
            filetype TEXT NOT NULL,
            upload_time TEXT NOT NULL,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        )
        """
    )
    db.commit()


@app.teardown_appcontext
def close_db(_: Any) -> None:
    db = g.pop("db", None)
    if db is not None:
        db.close()


@app.errorhandler(413)
def file_too_large(_: Any):
    return jsonify({"error": "File too large. Max size is 16MB."}), 413


@app.errorhandler(404)
def not_found(_: Any):
    return jsonify({"error": "Resource not found"}), 404


def get_current_user_id() -> int:
    user_id = session.get("user_id")
    if user_id:
        return int(user_id)

    # Fallback option for API clients that pass user id explicitly.
    header_user_id = request.headers.get("X-User-Id") or request.args.get("user_id")
    if header_user_id and str(header_user_id).isdigit():
        return int(header_user_id)

    abort(401, description="Unauthorized. Login or pass X-User-Id.")


@app.errorhandler(401)
def unauthorized(error: Any):
    message = getattr(error, "description", "Unauthorized")
    return jsonify({"error": message}), 401


@app.route("/register", methods=["POST"])
def register():
    data = request.get_json(silent=True) or request.form
    username = (data.get("username") or "").strip()

    if not username:
        return jsonify({"error": "Username is required"}), 400

    db = get_db()
    try:
        cur = db.execute("INSERT INTO users (username) VALUES (?)", (username,))
        db.commit()
    except sqlite3.IntegrityError:
        return jsonify({"error": "Username already exists"}), 409

    session["user_id"] = cur.lastrowid
    session["username"] = username

    return jsonify({"message": "Registered successfully", "user_id": cur.lastrowid, "username": username}), 201
