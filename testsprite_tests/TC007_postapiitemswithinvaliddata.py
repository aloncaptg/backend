import requests

BASE_URL = "http://localhost:8000"
LOGIN_URL = f"{BASE_URL}/api/login"
ITEMS_URL = f"{BASE_URL}/api/items"
TIMEOUT = 30

def test_postapiitemswithinvaliddata():
    # Use known valid user credentials for login (replace with valid test user)
    login_payload = {
        "email": "testuser@example.com",
        "password": "TestPassword123!"
    }
    # Login to get auth token
    try:
        login_resp = requests.post(LOGIN_URL, json=login_payload, timeout=TIMEOUT)
        assert login_resp.status_code == 200, f"Login failed with status code {login_resp.status_code}"
        resp_json = login_resp.json()
        token = resp_json.get("token") or resp_json.get("access_token")
        assert token is not None, f"Login response missing token fields: {resp_json}"
    except Exception as e:
        assert False, f"Login failed: {e}"

    headers = {
        "Authorization": f"Bearer {token}",
        "Content-Type": "application/json"
    }

    # Prepare invalid item data (empty required fields, missing required fields, or wrong types)
    invalid_payloads = [
        {},  # completely empty payload
        {"name": ""},  # empty name if name is required to be non-empty string
        {"name": "Valid Name", "quantity": -10},  # negative quantity if not allowed
        {"name": "Valid Name", "price": "not_a_number"},  # price should be numeric
        {"quantity": 5},  # missing required name field if required
    ]

    for payload in invalid_payloads:
        try:
            resp = requests.post(ITEMS_URL, json=payload, headers=headers, timeout=TIMEOUT)
        except Exception as e:
            assert False, f"Request failed: {e}"
        # Validate that response status is 422 Unprocessable Entity for validation errors
        assert resp.status_code == 422, f"Expected status 422 but got {resp.status_code} for payload {payload}"

test_postapiitemswithinvaliddata()