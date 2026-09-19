import requests

BASE_URL = "http://localhost:8000"
LOGIN_ENDPOINT = "/api/login"
TIMEOUT = 30

def test_postapiloginwithinvalidcredentials():
    url = BASE_URL + LOGIN_ENDPOINT
    invalid_credentials = {
        "email": "invaliduser@example.com",
        "password": "wrongpassword"
    }
    try:
        response = requests.post(url, json=invalid_credentials, timeout=TIMEOUT)
    except requests.RequestException as e:
        assert False, f"Request failed: {e}"

    assert response.status_code == 401, f"Expected status code 401, got {response.status_code}"
    try:
        response_json = response.json()
    except ValueError:
        assert False, "Response is not valid JSON"
    
    # Assert no token is present
    assert "token" not in response_json, "Token should not be present on invalid login"

test_postapiloginwithinvalidcredentials()