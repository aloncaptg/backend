import requests
import uuid

BASE_URL = "http://localhost:8000"
TIMEOUT = 30

def test_post_api_v4_wastes_with_valid_data_and_token():
    # Step 1: Login to get auth token (using a known test user)
    login_url = f"{BASE_URL}/api/login"
    login_payload = {
        "email": "testemployee@example.com",
        "password": "TestPassword123!"
    }
    try:
        login_response = requests.post(login_url, json=login_payload, timeout=TIMEOUT)
        assert login_response.status_code == 200, f"Login failed: {login_response.text}"
        token = login_response.json().get("token")
        assert token, "Authentication token not found in login response"
    except Exception as e:
        raise Exception(f"Authentication failed: {e}")

    headers = {
        "Authorization": f"Bearer {token}",
        "Content-Type": "application/json"
    }

    # Step 2: Retrieve current inventory items to later verify inventory update
    items_url = f"{BASE_URL}/api/items"
    try:
        items_response = requests.get(items_url, headers=headers, timeout=TIMEOUT)
        assert items_response.status_code == 200, f"Failed to get inventory items: {items_response.text}"
        inventory_before = {item["id"]: item for item in items_response.json()}
    except Exception as e:
        raise Exception(f"Failed retrieving inventory items: {e}")

    # Step 3: Submit waste log with valid data
    wastes_url = f"{BASE_URL}/api/v4/wastes"
    # Construct a valid waste payload
    # Assuming required fields from typical waste log: item_id, quantity, reason, notes (notes optional)
    # Use an existing inventory item for item_id
    if not inventory_before:
        raise Exception("No inventory items available to create a waste log")
    sample_item_id = next(iter(inventory_before.keys()))
    waste_payload = {
        "item_id": sample_item_id,
        "quantity": 1,
        "reason": "Expired",
        "notes": f"Test waste log {uuid.uuid4()}"
    }

    try:
        waste_response = requests.post(wastes_url, json=waste_payload, headers=headers, timeout=TIMEOUT)
        assert waste_response.status_code == 201, f"Waste log creation failed: {waste_response.text}"
        waste_record = waste_response.json()
        assert waste_record.get("id"), "Created waste log ID missing in response"
    except Exception as e:
        raise Exception(f"Failed creating waste log: {e}")

    waste_id = waste_record.get("id")

    # Step 4: Retrieve updated inventory to verify the quantity reduced
    try:
        updated_items_response = requests.get(items_url, headers=headers, timeout=TIMEOUT)
        assert updated_items_response.status_code == 200, f"Failed to get inventory items after waste: {updated_items_response.text}"
        inventory_after = {item["id"]: item for item in updated_items_response.json()}
    except Exception as e:
        raise Exception(f"Failed retrieving updated inventory items: {e}")

    # Validate inventory quantity decreased by waste quantity for the item
    qty_before = inventory_before[sample_item_id].get("quantity")
    qty_after = inventory_after.get(sample_item_id, {}).get("quantity")

    assert qty_before is not None and qty_after is not None, "Quantity field missing in inventory items"
    assert qty_after == qty_before - waste_payload["quantity"], (
        f"Inventory quantity did not decrease correctly after waste log. Before: {qty_before}, After: {qty_after}"
    )


test_post_api_v4_wastes_with_valid_data_and_token()