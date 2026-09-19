import requests

BASE_URL = "http://localhost:8000"
LOGIN_ENDPOINT = "/api/login"
ITEMS_ENDPOINT = "/api/items"
TIMEOUT = 30

def test_get_api_items_with_valid_token():
    login_url = BASE_URL + LOGIN_ENDPOINT
    items_url = BASE_URL + ITEMS_ENDPOINT

    # Replace with valid user credentials known to exist in the system
    user_credentials = {
        "email": "testuser@example.com",
        "password": "TestPassword123!"
    }

    try:
        # Authenticate and get token
        login_resp = requests.post(login_url, json=user_credentials, timeout=TIMEOUT)
        assert login_resp.status_code == 200, f"Login failed with status code {login_resp.status_code}"
        login_json = login_resp.json()
        assert "token" in login_json or "access_token" in login_json, "No auth token found in login response"
        token = login_json.get("token") or login_json.get("access_token")
        assert isinstance(token, str) and len(token) > 0, "Auth token is empty or invalid"

        headers = {
            "Authorization": f"Bearer {token}"
        }

        # Request inventory items with valid token
        items_resp = requests.get(items_url, headers=headers, timeout=TIMEOUT)
        assert items_resp.status_code == 200, f"Expected 200 OK but got {items_resp.status_code}"

        items_json = items_resp.json()
        assert isinstance(items_json, list) or isinstance(items_json, dict), "Inventory response is not JSON list or dict"
        # If items_json is dict, it might contain metadata with items list, so allow both

    except requests.RequestException as e:
        assert False, f"Request error occurred: {e}"

test_get_api_items_with_valid_token()