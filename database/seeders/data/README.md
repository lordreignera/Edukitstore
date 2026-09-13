# School Seeder Data

`UgandaSchoolSeeder` reads `database/seeders/data/uganda_schools.csv`.

Use the same headers when replacing the starter file with the full client or EMIS school master list:

```csv
name,district,school_code,location,contact_person,contact_phone,contact_email,distance_from_warehouse_km,delivery_fee,notes
```

The required fields are `name` and `district`. All other fields can be blank, but `delivery_fee` should be set before a school is made available at checkout.

After updating the CSV, run:

```bash
php artisan db:seed --class=UgandaSchoolSeeder
```
