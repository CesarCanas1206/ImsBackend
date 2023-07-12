<?php
namespace App\Http\Controllers;

class CreateSiteController extends APIController
{
    protected $db;
    protected $client;
    protected $new;
    protected $template = 'bookings';
    protected $database;

    public function __construct()
    {
        date_default_timezone_set("Australia/Brisbane");
        // Connect to db user
        $this->db = mysqli_connect(env('DB_HOST'), env('DB_MASTER_USERNAME'), env('DB_MASTER_PASSWORD'));
        if (!$this->db) {
            die('Could not connect: to database');
        }
    }

    public function createSite()
    {
        if (!isset(request()->v) || request()->v !== 'e86532f0-09a2-4229-8497-8432f9f02869') {
            die('Code not sent');
        }
        $this->new = request()->site ?? '';
        if (empty($this->new)) {
            die('Site not sent');
        }
        $this->createDatabaseAndUser();
        $this->createTablesFromTemplate();
        $this->addConfigSettings();

        return response()->json(['message' => 'Site created']);
    }

    public function query($query = '')
    {
        return mysqli_query($this->db, $query);
    }

    public function createDatabaseAndUser()
    {
        $client = substr($this->new, 0, 12);
        $this->database = str_replace('{site}', $this->new, env('DB_DATABASE'));
        $username = str_replace('{site}', $client, env('DB_USERNAME'));
        $password = str_replace('{site}', $client, env('DB_PASSWORD'));

        $check = $this->query(
            "SELECT SCHEMA_NAME
            FROM INFORMATION_SCHEMA.SCHEMATA
            WHERE SCHEMA_NAME = '{$this->database}'"
        );

        if (mysqli_num_rows($check) !== 0) {
            die('Database already exists');
        }

        $queries = [
            "CREATE USER '${username}'@'%' IDENTIFIED BY '${password}'",
            "GRANT SELECT,UPDATE,INSERT,DELETE ON {$this->database}.* TO '${username}'@'%'",
            "FLUSH PRIVILEGES",
            "CREATE DATABASE IF NOT EXISTS {$this->database}",
        ];

        foreach ($queries as $query) {
            $this->query($query) or die(\mysqli_error($this->db));
        }
    }

    public function createTablesFromTemplate()
    {
        $template = str_replace('{site}', $this->template, env('DB_DATABASE'));
        $result = $this->query(
            "SELECT TABLE_SCHEMA,TABLE_NAME
            FROM INFORMATION_SCHEMA.TABLES
            WHERE TABLE_SCHEMA = '" . $template . "' "
        );

        $includeData = ['permission', 'role', 'dataset'];
        // Copy across table structure and content (if not in skipData)
        while ($row = mysqli_fetch_assoc($result)) {
            $table = $row['TABLE_NAME'];
            //create the table
            $this->query(
                "CREATE TABLE IF NOT EXISTS
                `" . $this->database . "`.`" . $table . "`
                LIKE `" . $template . "`.`" . $table . "`"
            );

            if ($table == 'users') {
                $this->query(
                    "INSERT INTO `" . $this->database . "`.`" . $table . "`
                    (id, email, password, first_name, last_name, role_id, ims_account)
                    SELECT
                    id, email, password, first_name, last_name, role_id, ims_account
                    FROM `" . $template . "`.`" . $table . "`
                    WHERE `" . $template . "`.`" . $table . "`.`ims_account` = 1"
                );
            }

            if (in_array($table, $includeData)) {
                $this->query(
                    "INSERT INTO `" . $this->database . "`.`" . $table . "`
                    SELECT * FROM `" . $template . "`.`" . $table . "`"
                );
            }
        }
    }

    public function addConfigSettings()
    {
        if (!empty(request()->modules)) {
            $modules = json_encode(explode(',', request()->modules));
            $this->query(
                "INSERT INTO `" . $this->database . "`.`config`
                SET `id` = 'fc058f55-63c1-11ed-9fe0-54cd06b88880', `name` = 'Modules', `code` = 'modules',
                    `type` = 'text', `value` = '" . $modules . "'"
            );
        }
        if (!empty(request()->name)) {
            $name = addSlashes(request()->name);
            $this->query(
                "INSERT INTO `" . $this->database . "`.`config`
                SET `id` = 'fc059a57-63c1-11ed-9fe0-54cd06b88880', `name` = 'Name', `code` = 'name',
                    `type` = 'text', `value` = '" . $name . "'"
            );
        }
    }
}
