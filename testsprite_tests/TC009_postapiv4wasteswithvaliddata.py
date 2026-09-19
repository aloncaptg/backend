import requests

BASE_URL = "http://localhost:8000"
LOGIN_ENDPOINT = "/api/login"
WASTES_ENDPOINT = "/api/v4/wastes"
TIMEOUT = 30

def test_post_apiv4_wastes_with_valid_data():
    # Step 1: Login to get auth token
    login_payload = {
        "email": "employee@example.com",
        "password": "StrongPassword123"
    }
    try:
        login_response = requests.post(
            BASE_URL + LOGIN_ENDPOINT,
            json=login_payload,
            timeout=TIMEOUT
        )
        assert login_response.status_code == 200, f"Login failed, status code: {login_response.status_code}"
        token = login_response.json().get("token") or login_response.json().get("access_token")
        assert token, "Authentication token not found in login response"
    except requests.RequestException as e:
        assert False, f"Login request failed: {e}"

    headers = {
        "Authorization": f"Bearer {token}",
        "Content-Type": "application/json"
    }

    # Step 2: Prepare valid waste log data
    waste_data = {
        "reason": "Expired ingredients",
        "quantity": 5,
        "unit": "kg",
        "description": "Expired tomatoes discarded",
        # Include other fields if required by API schema
    }

    # Step 3: Submit waste log
    try:
        response = requests.post(
            BASE_URL + WASTES_ENDPOINT,
            json=waste_data,
            headers=headers,
            timeout=TIMEOUT
        )
        assert response.status_code == 201, f"Expected status 201 but got {response.status_code}"
        resp_json = response.json()
        assert "id" in resp_json or "waste_id" in resp_json, "Created waste log ID not found in response"
        # Additional assertions can be done based on response body
    except requests.RequestException as e:
        assert False, f"POST /api/v4/wastes request failed: {e}"

test_post_apiv4_wastes_with_valid_data()