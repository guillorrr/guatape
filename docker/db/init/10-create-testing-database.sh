#!/bin/bash
# Creates the database used by the test suite ("<DB_DATABASE>_testing", see
# config/database.php `mysql_testing`) and grants it to the app user.
# MySQL runs this only on the first start, when the data volume is empty.
set -e

mysql -uroot -p"$MYSQL_ROOT_PASSWORD" <<SQL
CREATE DATABASE IF NOT EXISTS \`${MYSQL_DATABASE}_testing\`;
GRANT ALL PRIVILEGES ON \`${MYSQL_DATABASE}_testing\`.* TO '${MYSQL_USER}'@'%';
FLUSH PRIVILEGES;
SQL
