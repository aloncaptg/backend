import requests

BASE_URL = "http://localhost:8000"
LOGIN_URL = f"{BASE_URL}/api/login"
ITEMS_URL = f"{BASE_URL}/api/items"
HEADERS_JSON = {"Content-Type": "application/json"}
TIMEOUT = 30


def test_post_api_items_with_valid_data():
    # Valid credentials for login - adjust with valid test user credentials
    login_payload = {
        "email": "testuser@example.com",
        "password": "TestPassword123"
    }

    session = requests.Session()

    # Step 1: Log in to get auth token
    try:
        login_resp = session.post(LOGIN_URL, json=login_payload, timeout=TIMEOUT, headers=HEADERS_JSON)
        assert login_resp.status_code == 200, f"Login failed with status {login_resp.status_code}"
        login_data = login_resp.json()
        token = login_data.get("token") or login_data.get("access_token")
        assert token, "Authentication token not found in login response"

        auth_headers = {
            "Authorization": f"Bearer {token}",
            "Content-Type": "application/json"
        }

        # Step 2: Create a new inventory item with valid data
        # Sample valid inventory item data; adapt fields as necessary based on API expectations
        item_payload = {
            "name": "Test Item TC006",
            "description": "Item created in test case TC006",
            "quantity": 10,
            "unit": "pcs",
            "price": 5.50,
            "category": "Test Category"
        }

        post_resp = session.post(ITEMS_URL, json=item_payload, headers=auth_headers, timeout=TIMEOUT)
        assert post_resp.status_code == 201, f"Expected 201 Created but got {post_resp.status_code}"
        created_item = post_resp.json()
        # Validate returned item data contains expected fields and correct values
        assert created_item.get("name") == item_payload["name"], "Created item name mismatch"
        assert created_item.get("quantity") == item_payload["quantity"], "Created item quantity mismatch"

    finally:
        # Cleanup: delete the created inventory item to keep test environment clean
        if 'created_item' in locals() and created_item.get("id"):
            item_id = created_item["id"]
            try:
                del_resp = session.delete(f"{ITEMS_URL}/{item_id}", headers=auth_headers, timeout=TIMEOUT)
                # If delete is successful or not found, ignore errors in cleanup
                assert del_resp.status_code in (200, 204, 404), f"Unexpected delete status {del_resp.status_code}"
            except Exception:
                pass


test_post_api_items_with_valid_data()