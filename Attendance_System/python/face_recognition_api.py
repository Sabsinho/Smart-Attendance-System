from flask import Flask, request, jsonify
from flask_cors import CORS
import face_recognition
import numpy as np
import cv2
import base64
import os

app = Flask(__name__)
CORS(app)

known_face_encodings = []
known_face_ids = []


def load_known_faces():
    path = "known_faces"

    if not os.path.exists(path):
        os.makedirs(path)

    for file in os.listdir(path):
        if file.endswith(".jpg") or file.endswith(".png"):

            img_path = os.path.join(path, file)
            image = face_recognition.load_image_file(img_path)

            rgb = cv2.cvtColor(image, cv2.COLOR_BGR2RGB)

            encodings = face_recognition.face_encodings(rgb)

            if len(encodings) > 0:
                known_face_encodings.append(encodings[0])
                student_id = os.path.splitext(file)[0]
                known_face_ids.append(student_id)

    print(f"[INFO] Loaded {len(known_face_encodings)} known faces")


load_known_faces()


@app.route("/recognize", methods=["POST"])
def recognize():
    try:
        data = request.json

        if not data or "image" not in data:
            return jsonify({"success": False, "error": "No image provided"})

        img_data = base64.b64decode(data["image"])
        np_arr = np.frombuffer(img_data, np.uint8)
        frame = cv2.imdecode(np_arr, cv2.IMREAD_COLOR)

        rgb = cv2.cvtColor(frame, cv2.COLOR_BGR2RGB)

        face_locations = face_recognition.face_locations(rgb)
        face_encodings = face_recognition.face_encodings(rgb, face_locations)

        if len(face_encodings) == 0:
            return jsonify({
                "success": True,
                "student_id": "Unknown"
            })

        if len(known_face_encodings) == 0:
            return jsonify({
                "success": False,
                "error": "No known faces loaded"
            })

        encoding = face_encodings[0]

        distances = face_recognition.face_distance(
            known_face_encodings,
            encoding
        )

        best_index = np.argmin(distances)
        best_distance = distances[best_index]

        print("Best distance:", best_distance)

        # stricter but stable threshold
        if best_distance < 0.55:
            student_id = known_face_ids[best_index]
        else:
            student_id = "Unknown"

        return jsonify({
            "success": True,
            "student_id": student_id
        })

    except Exception as e:
        return jsonify({
            "success": False,
            "error": str(e)
        })


if __name__ == "__main__":
    app.run(host="0.0.0.0", port=5000, debug=True)