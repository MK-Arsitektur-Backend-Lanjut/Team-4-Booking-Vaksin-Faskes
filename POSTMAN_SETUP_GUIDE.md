# POSTMAN API TESTING GUIDE

## Environment Setup

### 1. Import Postman Collection

1. Open **Postman**
2. Click **File** → **Import** (or use the **Import** button)
3. Select the file: `Module1_Postman_Collection.json`
4. The collection will be imported with all endpoints pre-configured

### 2. Verify Base URL Variable

1. In Postman, go to **Collections** → **Module 1 - Vaccine Management API**
2. Click the **Variables** tab
3. Verify that `base_url` is set to: `http://localhost:8000/api`
4. If not, update it to this URL
5. Click **Save**

## Available Endpoints

### Health Centers

- **List All Health Centers** - `GET /health-centers?per_page=15`
- **Get Health Center By ID** - `GET /health-centers/2` (ID 2 exists in database)
- **Create Health Center** - `POST /health-centers`
- **Update Health Center** - `PUT /health-centers/2`
- **Delete Health Center** - `DELETE /health-centers/2`
- **Get Active Health Centers** - `GET /health-centers/active`
- **Search Health Centers** - `GET /health-centers/search?q=jakarta`
- **Get By Province** - `GET /health-centers/by-province/JAKARTA`
- **Get By City** - `GET /health-centers/by-city/JAKARTA%20PUSAT`

### Vaccines

- **List All Vaccines** - `GET /vaccines`
- **Get Vaccine By ID** - `GET /vaccines/1`
- **Create Vaccine** - `POST /vaccines`
- **Update Vaccine** - `PUT /vaccines/1`
- **Delete Vaccine** - `DELETE /vaccines/1`
- **Get Active Vaccines** - `GET /vaccines/active`
- **Search Vaccines** - `GET /vaccines/search?q=pfizer`

### Vaccine Stocks

- **List All Vaccine Stocks** - `GET /vaccine-stocks`
- **Get Vaccine Stock By ID** - `GET /vaccine-stocks/1`
- **Create Vaccine Stock** - `POST /vaccine-stocks`
- **Update Vaccine Stock** - `PUT /vaccine-stocks/1`
- **Delete Vaccine Stock** - `DELETE /vaccine-stocks/1`
- **Get Available Stocks** - `GET /vaccine-stocks/available`
- **Get By Health Center** - `GET /vaccine-stocks/health-center/2`

### Vaccine Schedules

- **List All Schedules** - `GET /vaccine-schedules`
- **Get Schedule By ID** - `GET /vaccine-schedules/1`
- **Create Schedule** - `POST /vaccine-schedules`
- **Update Schedule** - `PUT /vaccine-schedules/1`
- **Delete Schedule** - `DELETE /vaccine-schedules/1`
- **Get Available Schedules** - `GET /vaccine-schedules/available`
- **Get By Date** - `GET /vaccine-schedules/by-date?date=2026-04-23`
- **Get By Date Range** - `GET /vaccine-schedules/by-date-range?start_date=2026-04-23&end_date=2026-04-25`
- **Get By Health Center** - `GET /vaccine-schedules/health-center/2`

## Testing Instructions

### 1. Test GET Request (Get Health Center By ID)

1. Open the request: **Health Centers** → **Get Health Center By ID**
2. Click **Send**
3. You should receive **HTTP 200** with the health center data (ID 2)

```json
{
  "status": "success",
  "data": {
    "id": 2,
    "name": "Puskesmas JAKARTA PUSAT 2",
    "code": "JAK-00001",
    "address": "Jalan Vaksinasi No. 2, JAKARTA PUSAT",
    "province": "JAKARTA",
    "city": "JAKARTA PUSAT",
    ...
  }
}
```

### 2. Test PUT Request (Update Health Center)

1. Open the request: **Health Centers** → **Update Health Center**
2. The request body contains:
    ```json
    {
        "capacity": 200,
        "status": "active"
    }
    ```
3. Click **Send**
4. You should receive **HTTP 200** with updated data

### 3. Test DELETE Request (Delete Health Center)

1. Open the request: **Health Centers** → **Delete Health Center**
2. Click **Send**
3. You should receive **HTTP 200** with success message:
    ```json
    {
        "status": "success",
        "message": "Health center deleted successfully"
    }
    ```

## Troubleshooting

### Issue: "Cannot GET /health-centers/2"

**Solution:** Make sure:

1. Nginx server is running: `docker-compose ps`
2. Base URL is correctly set: `http://localhost:8000/api`
3. You're using a valid health center ID from the database (ID 2 exists)

### Issue: 404 Errors

**Solution:**

1. Check that all containers are running: `docker-compose ps`
2. Verify routes are registered: `docker-compose exec app php artisan route:list`
3. Restart containers if needed: `docker-compose restart`

### Issue: Connection Refused

**Solution:**

1. Start Docker containers: `docker-compose up -d`
2. Wait 10-15 seconds for services to be ready
3. Try again

## Data Available for Testing

### Health Centers

- **ID 2**: Puskesmas JAKARTA PUSAT 2 (Status: inactive → active after update)

### Vaccines

- **IDs 1-13**: Various vaccines (Pfizer, Moderna, AstraZeneca, Sinovac, Janssen, etc.)

### Vaccine Stocks

- Multiple entries per health center per vaccine

### Vaccine Schedules

- 7 days of schedules with morning and afternoon slots

## API Response Format

All endpoints return JSON in this format:

### Success Response

```json
{
  "status": "success",
  "data": { ... },
  "message": "..." (optional)
}
```

### Error Response

```json
{
    "status": "error",
    "message": "Error description"
}
```

## Next Steps

After verifying all endpoints work in Postman:

1. Test with different health center IDs (3, 4, 5, etc.)
2. Try searching and filtering endpoints
3. Test creating new health centers
4. Verify pagination with `?per_page=10` parameter
