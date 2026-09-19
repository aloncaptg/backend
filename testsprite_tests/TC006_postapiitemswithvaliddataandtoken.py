import requests

BASE_URL = "http://localhost:8000"
LOGIN_URL = f"{BASE_URL}/api/login"
ITEMS_URL = f"{BASE_URL}/api/items"
TIMEOUT = 30

# Credentials for login (should exist in the system for this test)
VALID_USER_CREDENTIALS = {
    "email": "testuser@example.com",
    "password": "Password123!"
}

def test_postapiitemswithvaliddataandtoken():
    # Step 1: Login to obtain auth token
    login_response = requests.post(LOGIN_URL, json=VALID_USER_CREDENTIALS, timeout=TIMEOUT)
    assert login_response.status_code == 200, f"Login failed with status {login_response.status_code}"
    login_json = login_response.json()
    assert "token" in login_json, "Auth token not found in login response"
    token = login_json["token"]

    headers = {
        "Authorization": f"Bearer {token}",
        "Content-Type": "application/json",
        "Accept": "application/json"
    }

    # Step 2: Define valid inventory item data
    item_data = {
        "name": "Test Item",
        "description": "An inventory test item created for TC006",
        "quantity": 10,
        "unit": "pcs",
        "category": "test-category"
    }

    created_item_id = None
    try:
        # Step 3: POST to /api/items with valid data and token
        create_response = requests.post(ITEMS_URL, json=item_data, headers=headers, timeout=TIMEOUT)
        assert create_response.status_code == 201, f"Expected 201 Created, got {create_response.status_code}"
        create_json = create_response.json()
        # Basic checks on returned created item structure
        assert isinstance(create_json, dict), "Response JSON is not an object"
        # Assuming ID is returned on create
        assert "id" in create_json, "Created item ID not found in response"
        created_item_id = create_json["id"]

        # Optionally confirm returned data matches sent data (except id)
        assert create_json.get("name") == item_data["name"], "Item name mismatch in response"
        assert create_json.get("description") == item_data["description"], "Item description mismatch in response"
        assert create_json.get("quantity") == item_data["quantity"], "Item quantity mismatch in response"
        assert create_json.get("unit") == item_data["unit"], "Item unit mismatch in response"
        assert create_json.get("category") == item_data["category"], "Item category mismatch in response"

    finally:
        # Cleanup: Delete the created item if it exists to keep test environment clean
        if created_item_id is not None:
            delete_url = f"{ITEMS_URL}/{created_item_id}"
            try:
                delete_response = requests.delete(delete_url, headers=headers, timeout=TIMEOUT)
                # Deletion may return 200 OK or 204 No Content; accept either
                assert delete_response.status_code in (200, 204), f"Failed to delete test item, status: {delete_response.status_code}"
            except Exception:
                # If deletion fails, do not raise to avoid masking test results, but log could be added if desired
                pass

test_postapiitemswithvaliddataandtoken()