# Keep shopping-list generation persistence-independent

Shopping-list generation accepts recipe selections with serving counts and returns aggregated ingredient requirements and purchase quantities without knowing whether the selections came from calendar entries or a temporary simple plan. Calendar persistence and UI workflows resolve their own data before invoking this domain service. This boundary keeps calculation rules testable and reusable, at the cost of requiring each planning workflow to adapt its data into the generator's input.
