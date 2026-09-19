import requests

BASE_URL = "http://localhost:8000"
TIMEOUT = 30

def test_get_api_deliveries_my_with_valid_token():
    """
    Test retrieving assigned deliveries for a driver with valid auth token to confirm 200 response with delivery list.
    """

    login_url = f"{BASE_URL}/api/login"
    deliveries_url = f"{BASE_URL}/api/deliveries/my"

    # Credentials for a driver user (should be valid and present in the test environment)
    driver_credentials = {
        "email": "driver@example.com",
        "password": "driverpassword"
    }

    token = None
    try:
        # Authenticate to get token
        login_resp = requests.post(login_url, json=driver_credentials, timeout=TIMEOUT)
        assert login_resp.status_code == 200, f"Login failed: {login_resp.status_code} {login_resp.text}"
        login_data = login_resp.json()
        assert "token" in login_data, "Token not present in login response"
        token = login_data["token"]

        headers = {
            "Authorization": f"Bearer {token}"
        }

        # Get deliveries assigned to the driver
        deliveries_resp = requests.get(deliveries_url, headers=headers, timeout=TIMEOUT)
        assert deliveries_resp.status_code == 200, f"Expected status 200, got {deliveries_resp.status_code}"

        deliveries_data = deliveries_resp.json()
        assert isinstance(deliveries_data, list), "Deliveries response is not a list"

    finally:
        if token:
            # Logout to clean session
            logout_url = f"{BASE_URL}/api/logout"
            try:
                requests.post(logout_url, headers={"Authorization": f"Bearer {token}"}, timeout=TIMEOUT)
            except Exception:
                pass

test_get_api_deliveries_my_with_valid_token()