# Veilo Postman API

## Import

1. Import `Veilo-V1.postman_collection.json` into Postman.
2. Import `Veilo-Local.postman_environment.json` and select `Veilo Local`.
3. Change `base_url` if the API is not running at `http://127.0.0.1:8000`.

## Recommended flow

1. Run `01 - Registration / Register - success` first.
2. Use `02 - Session / Refresh - success` while the refresh token is valid.
3. Use `02 - Session / Restore session by device secret - success` after normal refresh is no longer available.
4. Run `03 - Account Recovery` to test account recovery and Recovery Secret visibility.
5. Run `04 - Account Privacy` to change the authenticated account visibility.
6. Run logout requests last because they revoke the current device or all devices.
7. Run logout-current and logout-all as separate scenarios; after one succeeds, obtain a new session before testing the other.

The collection contains both success and failure examples. Do not run every request as one linear collection run: hidden/visible recovery-secret requests and logout requests intentionally change server state.

## Client implementation notes

- Store `access_token` in memory where possible and refresh it before expiry.
- Store `refresh_token` in protected platform storage and replace it after every successful refresh.
- Store `device_secret` only in iOS Keychain or Android Keystore-backed secure storage. Do not place it in ordinary preferences, local storage, logs, analytics, or crash reports.
- Never send `device_secret` except to `/api/v1/auth/session/restore`.
- Never log Authorization headers, refresh tokens, Recovery Secrets, or Device Secrets.
- Send `Accept: application/json` on every request and `Content-Type: application/json` on requests with a body.
- A 401 from a protected endpoint means the client should attempt refresh or device-secret restore according to the session state; it must not retry blindly.
- A 429 means the client must respect the server response and back off.

## Authentication headers

Protected endpoints require:

```http
Authorization: Bearer <access_token>
```

Public endpoints are registration, refresh, recovery, and device-secret restore. They are rate limited.

## Important response values

- Registration and recovery return `data.tokens.access_token` and `data.tokens.refresh_token`.
- Registration and recovery return `data.device_secret` exactly once for the newly created device.
- Device-secret restore returns new tokens but does not return the device secret.
- Validation errors normally return HTTP 422 with an `errors` object.
- Authentication and secret failures return HTTP 401 with a generic `message`.
- Recovery Secret hidden-state conflicts return HTTP 409.
