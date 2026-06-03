# API Documentation - Module 1: Health Center & Vaccine Management

## Base URL

```
http://localhost:8000/api
```

## Endpoints

### Health Centers

#### List All Health Centers

- **Method:** GET
- **URL:** `/health-centers`
- **Query Parameters:**
    - `per_page` (optional): Default 15
- **Response:**
    ```json
    {
      "status": "success",
      "data": [...],
      "pagination": {
        "total": 5000,
        "per_page": 15,
        "current_page": 1,
        "last_page": 334
      }
    }
    ```

#### Get Health Center By ID

- **Method:** GET
- **URL:** `/health-centers/{id}`
- **Response:**
    ```json
    {
        "status": "success",
        "data": {
            "id": 1,
            "name": "Puskesmas Jakarta Pusat 1",
            "code": "JAK-00001",
            "address": "Jalan Vaksinasi No. 1, JAKARTA PUSAT",
            "province": "JAKARTA",
            "city": "JAKARTA PUSAT",
            "district": "Kecamatan JAKARTA PUSAT",
            "village": "Kelurahan Vaksin 1",
            "latitude": -6.195,
            "longitude": 106.827,
            "phone": "+62-1234567890",
            "capacity": 150,
            "status": "active",
            "created_at": "2026-04-22T07:15:00Z",
            "updated_at": "2026-04-22T07:15:00Z"
        }
    }
    ```

#### Get Active Health Centers

- **Method:** GET
- **URL:** `/health-centers/active`
- **Response:** List of active health centers

#### Get Health Centers By Province

- **Method:** GET
- **URL:** `/health-centers/by-province/{province}`
- **Example:** `/health-centers/by-province/JAKARTA`

#### Get Health Centers By City

- **Method:** GET
- **URL:** `/health-centers/by-city/{city}`
- **Example:** `/health-centers/by-city/JAKARTA%20PUSAT`

#### Search Health Centers

- **Method:** GET
- **URL:** `/health-centers/search?q=jakarta`
- **Query Parameters:**
    - `q` (required): Minimum 2 characters

#### Create Health Center

- **Method:** POST
- **URL:** `/health-centers`
- **Body:**
    ```json
    {
        "name": "Puskesmas Baru",
        "code": "PBR-00001",
        "address": "Jalan Kesehatan No. 1",
        "province": "JAWA BARAT",
        "city": "BANDUNG",
        "district": "Kecamatan Bandung",
        "village": "Kelurahan Test",
        "latitude": -6.9175,
        "longitude": 107.6087,
        "phone": "+62-274123456",
        "capacity": 100,
        "status": "active"
    }
    ```

#### Update Health Center

- **Method:** PUT
- **URL:** `/health-centers/{id}`
- **Body:** (partial update allowed)
    ```json
    {
        "capacity": 200,
        "status": "inactive"
    }
    ```

#### Delete Health Center

- **Method:** DELETE
- **URL:** `/health-centers/{id}`

---

### Vaccines

#### List All Vaccines

- **Method:** GET
- **URL:** `/vaccines`
- **Query Parameters:**
    - `per_page` (optional): Default 15

#### Get Vaccine By ID

- **Method:** GET
- **URL:** `/vaccines/{id}`

#### Get Active Vaccines

- **Method:** GET
- **URL:** `/vaccines/active`

#### Search Vaccines

- **Method:** GET
- **URL:** `/vaccines/search?q=pfizer`

#### Create Vaccine

- **Method:** POST
- **URL:** `/vaccines`
- **Body:**
    ```json
    {
        "name": "Vaccine Baru",
        "code": "VAC-NEW-001",
        "description": "Deskripsi vaksin",
        "doses_required": 2,
        "days_between_doses": 21,
        "manufacturer": "Manufaktur ABC",
        "status": "active"
    }
    ```

#### Update Vaccine

- **Method:** PUT
- **URL:** `/vaccines/{id}`

#### Delete Vaccine

- **Method:** DELETE
- **URL:** `/vaccines/{id}`

---

### Vaccine Stocks

#### List All Vaccine Stocks

- **Method:** GET
- **URL:** `/vaccine-stocks`
- **Query Parameters:**
    - `per_page` (optional): Default 15

#### Get Vaccine Stock By ID

- **Method:** GET
- **URL:** `/vaccine-stocks/{id}`

#### Get Available Vaccine Stocks

- **Method:** GET
- **URL:** `/vaccine-stocks/available`
- **Description:** Returns only stocks with available_stock > 0

#### Get Vaccine Stocks By Health Center

- **Method:** GET
- **URL:** `/vaccine-stocks/health-center/{healthCenterId}`

#### Create Vaccine Stock

- **Method:** POST
- **URL:** `/vaccine-stocks`
- **Body:**
    ```json
    {
        "health_center_id": 1,
        "vaccine_id": 1,
        "total_stock": 500,
        "available_stock": 450,
        "expiration_date": "2027-12-31"
    }
    ```

#### Update Vaccine Stock

- **Method:** PUT
- **URL:** `/vaccine-stocks/{id}`
- **Body:** (partial update)
    ```json
    {
        "total_stock": 600,
        "available_stock": 550
    }
    ```

####Delete Vaccine Stock

- **Method:** DELETE
- **URL:** `/vaccine-stocks/{id}`

---

### Vaccine Schedules

#### List All Vaccine Schedules

- **Method:** GET
- **URL:** `/vaccine-schedules`
- **Query Parameters:**
    - `per_page` (optional): Default 15

#### Get Vaccine Schedule By ID

- **Method:** GET
- **URL:** `/vaccine-schedules/{id}`

#### Get Available Vaccine Schedules

- **Method:** GET
- **URL:** `/vaccine-schedules/available`
- **Description:** Returns only schedules with available_quota > 0

#### Get Vaccine Schedules By Health Center

- **Method:** GET
- **URL:** `/vaccine-schedules/health-center/{healthCenterId}`

#### Get Vaccine Schedules By Date

- **Method:** GET
- **URL:** `/vaccine-schedules/by-date?date=2026-04-25`
- **Query Parameters:**
    - `date` (required): Format YYYY-MM-DD

#### Get Vaccine Schedules By Date Range

- **Method:** GET
- **URL:** `/vaccine-schedules/by-date-range`
- **Query Parameters:**
    - `health_center_id` (required): Health center ID
    - `start_date` (required): Format YYYY-MM-DD
    - `end_date` (required): Format YYYY-MM-DD

#### Create Vaccine Schedule

- **Method:** POST
- **URL:** `/vaccine-schedules`
- **Body:**
    ```json
    {
        "health_center_id": 1,
        "vaccine_id": 1,
        "schedule_date": "2026-04-25",
        "start_time": "08:00",
        "end_time": "12:00",
        "quota": 100,
        "notes": "Jadwal vaksinasi reguler",
        "status": "scheduled"
    }
    ```

#### Update Vaccine Schedule

- **Method:** PUT
- **URL:** `/vaccine-schedules/{id}`
- **Body:** (partial update)
    ```json
    {
        "quota": 120,
        "status": "ongoing"
    }
    ```

#### Delete Vaccine Schedule

- **Method:** DELETE
- **URL:** `/vaccine-schedules/{id}`

---

## Error Responses

### 404 Not Found

```json
{
    "status": "error",
    "message": "Resource not found"
}
```

### 422 Unprocessable Entity

```json
{
    "status": "error",
    "message": "Validation error message"
}
```

---

## Testing with Postman

1. Import the following collection or manually create requests for each endpoint
2. Set the base URL to `http://localhost:8000/api`
3. Test the endpoints as documented above

### Example Postman Collection Variables:

- `base_url`: http://localhost:8000/api
- `health_center_id`: 1
- `vaccine_id`: 1
- `vaccine_stock_id`: 1
- `vaccine_schedule_id`: 1
