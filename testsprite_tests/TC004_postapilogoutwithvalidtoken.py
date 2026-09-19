import requests

BASE_URL = "http://localhost:8000"
TIMEOUT = 30

def postapilogoutwithvalidtoken():
    login_url = f"{BASE_URL}/api/login"
    logout_url = f"{BASE_URL}/api/logout"
    login_payload = {
        "email": "testuser@example.com",
        "password": "TestPassword123!"
    }
    headers = {"Content-Type": "application/json"}

    # First, login to get a valid token
    try:
        login_response = requests.post(login_url, json=login_payload, headers=headers, timeout=TIMEOUT)
        assert login_response.status_code == 200, f"Login failed with status code {login_response.status_code}"
        login_json = login_response.json()
        token = login_json.get("token")
        assert token, "No token found in login response"

        auth_headers = {
            "Authorization": f"Bearer {token}",
            "Content-Type": "application/json"
        }

        # Now, logout with this valid token
        logout_response = requests.post(logout_url, headers=auth_headers, timeout=TIMEOUT)
        assert logout_response.status_code == 200, f"Logout failed with status code {logout_response.status_code}"
    except (requests.RequestException, AssertionError) as e:
        raise e

postapilogoutwithvalidtoken()