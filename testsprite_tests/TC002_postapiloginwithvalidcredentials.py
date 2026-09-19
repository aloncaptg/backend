import requests

BASE_URL = "http://localhost:8000"


def test_post_api_login_with_valid_credentials():
    url = f"{BASE_URL}/api/login"
    payload = {
        "email": "testuser@example.com",
        "password": "P@ssw0rd123"
    }
    headers = {
        "Content-Type": "application/json",
        "Accept": "application/json"
    }
    try:
        response = requests.post(url, json=payload, headers=headers, timeout=30)
        assert response.status_code == 200, f"Expected status code 200 but got {response.status_code}"
        data = response.json()
        assert "token" in data or "access_token" in data, "Authentication token not found in response"
        token = data.get("token") or data.get("access_token")
        assert isinstance(token, str) and len(token) > 0, "Invalid authentication token received"
    except requests.RequestException as e:
        assert False, f"Request failed: {e}"


test_post_api_login_with_valid_credentials()