<?php

class Database
{
    public function connect(): PDO
    {
        $host = "localhost";
        $dbname = "coworking_db";
        $username = "root";
        $password = "";

        return new PDO(
            "mysql:host={$host};dbname={$dbname};charset=utf8mb4",
            $username,
            $password,
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            ]
        );
    }
}

class SpaceRepository
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function create(
        string $name,
        int $capacity,
        float $price,
        string $description
    ): void {
        $stmt = $this->pdo->prepare(
            "INSERT INTO spaces (name, capacity, price, description)
             VALUES (?, ?, ?, ?)"
        );

        $stmt->execute([$name, $capacity, $price, $description]);
    }

    public function getAll(): array
    {
        $stmt = $this->pdo->query("SELECT * FROM spaces");

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->pdo->prepare(
            "SELECT * FROM spaces WHERE id = ?"
        );
        $stmt->execute([$id]);

        $space = $stmt->fetch(PDO::FETCH_ASSOC);

        return $space ?: null;
    }

    public function update(
        int $id,
        string $name,
        int $capacity,
        float $price,
        string $description
    ): void {
        $stmt = $this->pdo->prepare(
            "UPDATE spaces
             SET name = ?, capacity = ?, price = ?, description = ?
             WHERE id = ?"
        );

        $stmt->execute([$name, $capacity, $price, $description, $id]);
    }

    public function delete(int $id): void
    {
        $stmt = $this->pdo->prepare(
            "DELETE FROM spaces WHERE id = ?"
        );

        $stmt->execute([$id]);
    }
}

class UserRepository
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function emailExists(string $email): bool
    {
        $stmt = $this->pdo->prepare(
            "SELECT id FROM users WHERE email = ?"
        );
        $stmt->execute([$email]);

        return (bool) $stmt->fetchColumn();
    }

    public function createUser(
        string $name,
        string $email,
        string $hashedPassword
    ): void {
        $stmt = $this->pdo->prepare(
            "INSERT INTO users (name, email, password)
             VALUES (?, ?, ?)"
        );

        $stmt->execute([$name, $email, $hashedPassword]);
    }

    public function findByEmail(string $email): ?array
    {
        $stmt = $this->pdo->prepare(
            "SELECT id, name, email, password, role
             FROM users
             WHERE email = ?
             LIMIT 1"
        );
        $stmt->execute([$email]);

        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        return $user ?: null;
    }
}

class ReservationRepository
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function getByUserId(int $userId): array
    {
        $stmt = $this->pdo->prepare(
            "SELECT
                reservations.id,
                spaces.name AS space_name,
                reservations.booking_date,
                reservations.start_time,
                reservations.end_time,
                reservations.status
             FROM reservations
             JOIN spaces
                ON spaces.id = reservations.space_id
             WHERE reservations.user_id = ?
             ORDER BY reservations.booking_date DESC,
                      reservations.start_time DESC"
        );

        $stmt->execute([$userId]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getAllWithDetails(): array
    {
        $stmt = $this->pdo->query(
            "SELECT
                reservations.id,
                users.name AS user_name,
                users.email AS user_email,
                spaces.name AS space_name,
                reservations.booking_date,
                reservations.start_time,
                reservations.end_time,
                reservations.status
             FROM reservations
             JOIN users
                ON users.id = reservations.user_id
             JOIN spaces
                ON spaces.id = reservations.space_id
             ORDER BY reservations.booking_date DESC,
                      reservations.start_time DESC"
        );

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function hasConflict(
        int $spaceId,
        string $bookingDate,
        string $startTime,
        string $endTime
    ): bool {
        $stmt = $this->pdo->prepare(
            "SELECT COUNT(*)
             FROM reservations
             WHERE space_id = ?
               AND booking_date = ?
               AND status IN ('pending', 'confirmed')
               AND start_time < ?
               AND end_time > ?"
        );

        $stmt->execute([
            $spaceId,
            $bookingDate,
            $endTime,
            $startTime
        ]);

        return (int) $stmt->fetchColumn() > 0;
    }

    public function create(
        int $userId,
        int $spaceId,
        string $bookingDate,
        string $startTime,
        string $endTime
    ): void {
        $stmt = $this->pdo->prepare(
            "INSERT INTO reservations
                (user_id, space_id, booking_date, start_time, end_time)
             VALUES (?, ?, ?, ?, ?)"
        );

        $stmt->execute([
            $userId,
            $spaceId,
            $bookingDate,
            $startTime,
            $endTime
        ]);
    }

    public function updateStatus(int $reservationId, string $status): void
    {
        $allowedStatuses = ["pending", "confirmed", "cancelled"];

        if (!in_array($status, $allowedStatuses, true)) {
            throw new InvalidArgumentException("Invalid reservation status.");
        }

        $stmt = $this->pdo->prepare(
            "UPDATE reservations SET status = ? WHERE id = ?"
        );

        $stmt->execute([$status, $reservationId]);
    }
}

function requireAdmin(): void
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }

    if (
        !isset($_SESSION["user_id"]) ||
        !isset($_SESSION["role"]) ||
        $_SESSION["role"] !== "admin"
    ) {
        header("Location: ../login.php");
        exit;
    }
}

try {
    $database = new Database();
    $pdo = $database->connect();
} catch (PDOException $e) {
    die("Database connection failed.");
}