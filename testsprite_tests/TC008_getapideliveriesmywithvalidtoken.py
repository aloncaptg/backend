import requests

BASE_URL = "http://localhost:8000"
LOGIN_ENDPOINT = "/api/login"
DELIVERIES_ENDPOINT = "/api/deliveries/my"
TIMEOUT = 30

def test_getapideliveriesmywithvalidtoken():
    # Assuming valid driver credentials - these should be replaced with actual valid test credentials
    login_payload = {
        "email": "driver@example.com",
        "password": "driverpassword"
    }
    try:
        # Login to get auth token
        login_response = requests.post(
            f"{BASE_URL}{LOGIN_ENDPOINT}",
            json=login_payload,
            timeout=TIMEOUT
        )
        assert login_response.status_code == 200, f"Login failed with status code {login_response.status_code}"
        login_data = login_response.json()
        assert "token" in login_data, "Auth token not found in login response"
        token = login_data["token"]

        headers = {
            "Authorization": f"Bearer {token}"
        }

        # Get assigned deliveries
        deliveries_response = requests.get(
            f"{BASE_URL}{DELIVERIES_ENDPOINT}",
            headers=headers,
            timeout=TIMEOUT
        )
        assert deliveries_response.status_code == 200, f"Deliveries retrieval failed with status code {deliveries_response.status_code}"
        deliveries_data = deliveries_response.json()

        # Validate that the deliveries_data is a list or dict containing delivery info
        assert deliveries_data is not None, "Deliveries response JSON is None"
        assert isinstance(deliveries_data, (list, dict)), f"Expected deliveries response to be list or dict, got {type(deliveries_data)}"

    except requests.RequestException as e:
        assert False, f"Request failed: {e}"

test_getapideliveriesmywithvalidtoken()