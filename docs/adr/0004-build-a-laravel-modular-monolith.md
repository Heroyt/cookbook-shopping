# Build a Laravel modular monolith

The application will be one Laravel deployment and database organized into explicit Family Access, Cookbook, Meal Planning, and Shopping Generation modules. Module boundaries are enforced in code and Shopping Generation remains persistence-independent, but modules communicate in process rather than through network services. This preserves DDD separation and testability without introducing distributed-system overhead for a small household application.
