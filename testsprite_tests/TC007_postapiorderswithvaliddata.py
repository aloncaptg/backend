import requests

BASE_URL = "http://localhost:8000"
LOGIN_URL = f"{BASE_URL}/api/login"
ORDERS_URL = f"{BASE_URL}/api/orders"

def test_post_api_orders_with_valid_data():
    login_payload = {
        "email": "testuser@example.com",
        "password": "TestPassword123!"
    }
    try:
        # Login to get auth token
        login_response = requests.post(LOGIN_URL, json=login_payload, timeout=30)
        assert login_response.status_code == 200, f"Login failed with status {login_response.status_code}"
        token = login_response.json().get("token") or login_response.json().get("access_token")
        assert token, "Authentication token not found in login response"
        headers = {
            "Authorization": f"Bearer {token}",
            "Content-Type": "application/json"
        }
        # Prepare valid order data - considering a basic order with plausible fields
        order_payload = {
            "customer_name": "John Doe",
            "customer_phone": "555-1234567",
            "order_items": [
                {
                    "item_id": 1,
                    "quantity": 2
                },
                {
                    "item_id": 2,
                    "quantity": 1
                }
            ],
            "delivery_date": "2026-09-01T12:00:00Z",
            "notes": "Please ring the bell twice."
        }
        response = requests.post(ORDERS_URL, json=order_payload, headers=headers, timeout=30)
        assert response.status_code == 201, f"Expected 201 Created, got {response.status_code}"
        response_data = response.json()
        assert isinstance(response_data, dict), "Response is not a JSON object"
        assert "id" in response_data, "Created order ID is missing in response"
        assert response_data.get("customer_name") == order_payload["customer_name"], "Customer name mismatch"
        # Optionally check order items presence
        assert "order_items" in response_data, "Order items missing in response"
    finally:
        # Cleanup: delete the created order if created
        if 'response' in locals() and response.status_code == 201:
            order_id = response.json().get("id")
            if order_id:
                delete_response = requests.delete(f"{ORDERS_URL}/{order_id}", headers=headers, timeout=30)
                # Ignore deletion error but can assert if needed:
                assert delete_response.status_code in (200, 204), f"Failed to delete order {order_id}"

test_post_api_orders_with_valid_data()