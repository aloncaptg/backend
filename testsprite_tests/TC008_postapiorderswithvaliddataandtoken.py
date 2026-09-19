import requests

BASE_URL = "http://localhost:8000"
TIMEOUT = 30

def test_post_api_orders_with_valid_data_and_token():
    # First, login to get a valid auth token
    login_url = f"{BASE_URL}/api/login"
    login_payload = {
        "email": "testuser@example.com",
        "password": "testpassword123"
    }
    headers = {"Content-Type": "application/json"}
    try:
        login_response = requests.post(login_url, json=login_payload, headers=headers, timeout=TIMEOUT)
        assert login_response.status_code == 200, f"Login failed with status {login_response.status_code}"
        token = login_response.json().get("token")
        assert token, "Token not found in login response"
    except Exception as e:
        raise AssertionError(f"Login request failed: {str(e)}")

    order_url = f"{BASE_URL}/api/orders"
    order_payload = {
        "customer_name": "John Doe",
        "order_date": "2026-08-19",
        "items": [
            {
                "item_id": 1,
                "quantity": 3
            },
            {
                "item_id": 2,
                "quantity": 1
            }
        ],
        "notes": "Please deliver between 12pm and 1pm"
    }
    auth_headers = {
        "Authorization": f"Bearer {token}",
        "Content-Type": "application/json"
    }

    created_order_id = None
    try:
        response = requests.post(order_url, json=order_payload, headers=auth_headers, timeout=TIMEOUT)
        assert response.status_code == 201, f"Expected status 201, got {response.status_code}"
        response_data = response.json()
        created_order_id = response_data.get("id")
        assert created_order_id is not None, "Created order ID not found in response"
        # Additional assertions on response data can be added here if schema known

    except Exception as e:
        raise AssertionError(f"Order creation failed: {str(e)}")
    finally:
        # Cleanup: Delete the created order if possible
        if created_order_id:
            delete_url = f"{BASE_URL}/api/orders/{created_order_id}"
            try:
                del_response = requests.delete(delete_url, headers=auth_headers, timeout=TIMEOUT)
                # It's fine if delete is not supported or fails, just proceed
            except:
                pass

test_post_api_orders_with_valid_data_and_token()