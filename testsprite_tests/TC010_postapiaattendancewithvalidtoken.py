import requests
import datetime
import time

BASE_URL = "http://localhost:8000"
TIMEOUT = 30

def test_postapiaattendancewithvalidtoken():
    # Step 1: Log in to get a valid auth token
    login_url = f"{BASE_URL}/api/login"
    login_payload = {
        "email": "employee@example.com",
        "password": "Password123!"
    }
    headers = {"Content-Type": "application/json"}

    try:
        login_response = requests.post(login_url, json=login_payload, headers=headers, timeout=TIMEOUT)
        assert login_response.status_code == 200, f"Login failed with status code {login_response.status_code}"
        token = login_response.json().get("token")
        assert token, "No token returned in login response"
    except requests.RequestException as e:
        assert False, f"Login request failed: {e}"

    auth_headers = {
        "Authorization": f"Bearer {token}",
        "Content-Type": "application/json"
    }

    attendance_url = f"{BASE_URL}/api/attendance"

    # Step 2: Post clock-in attendance data
    clock_in_payload = {
        "type": "clock_in",
        "gps": {
            "lat": 40.7128,
            "lng": -74.0060
        },
        "photo": "base64EncodedImageString=="
    }

    try:
        clock_in_response = requests.post(attendance_url, json=clock_in_payload, headers=auth_headers, timeout=TIMEOUT)
        assert clock_in_response.status_code == 200, f"Clock-in failed with status code {clock_in_response.status_code}"
        json_data = clock_in_response.json()
        assert json_data.get("message") or json_data.get("status") == "success" or json_data.get("attendance_id") is not None, "Clock-in response missing success confirmation"
    except requests.RequestException as e:
        assert False, f"Clock-in request failed: {e}"

    # Wait some time to simulate a passage of hours (in real test, an actual shift duration)
    time.sleep(1)

    # Step 3: Post clock-out attendance data
    clock_out_payload = {
        "type": "clock_out",
        "gps": {
            "lat": 40.7128,
            "lng": -74.0060
        },
        "photo": "base64EncodedImageString=="
    }

    try:
        clock_out_response = requests.post(attendance_url, json=clock_out_payload, headers=auth_headers, timeout=TIMEOUT)
        assert clock_out_response.status_code == 200, f"Clock-out failed with status code {clock_out_response.status_code}"
        json_data = clock_out_response.json()
        assert json_data.get("message") or json_data.get("status") == "success", "Clock-out response missing success confirmation"
    except requests.RequestException as e:
        assert False, f"Clock-out request failed: {e}"

test_postapiaattendancewithvalidtoken()