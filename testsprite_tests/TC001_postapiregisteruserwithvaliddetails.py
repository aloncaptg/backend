import requests

BASE_URL = "http://localhost:8000"
REGISTER_ENDPOINT = "/api/register"
TIMEOUT = 30

def test_post_api_register_user_with_valid_details():
    url = BASE_URL + REGISTER_ENDPOINT
    headers = {
        "Content-Type": "application/json",
        "Accept": "application/json"
    }
    # Sample valid user details for registration
    import time
    payload = {
        "name": "Test User",
        "email": f"testuser_{int(time.time())}@example.com",
        "password": "StrongPassword123!",
        "password_confirmation": "StrongPassword123!"
    }

    try:
        response = requests.post(url, json=payload, headers=headers, timeout=TIMEOUT)
    except requests.RequestException as e:
        assert False, f"Request failed: {e}"

    assert response.status_code == 201, f"Expected status 201, got {response.status_code}"

    # Check response content - it should contain created user info or at least an id.
    try:
        resp_json = response.json()
    except ValueError:
        assert False, "Response is not valid JSON"

    assert "id" in resp_json or "user" in resp_json, "Response JSON does not contain user id or user object"
    # Optionally validate that the returned user email matches
    if "user" in resp_json and isinstance(resp_json["user"], dict):
        assert resp_json["user"].get("email") == payload["email"], "Returned user email does not match registration email"
    elif "email" in resp_json:
        assert resp_json.get("email") == payload["email"], "Returned email does not match registration email"

test_post_api_register_user_with_valid_details()
