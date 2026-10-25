<?php
date_default_timezone_set("Asia/Amman");
class Database {
 public function connect(): PDO { return new PDO("mysql:host=localhost;dbname=coworking_db;charset=utf8mb4","root","",[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC,PDO::ATTR_EMULATE_PREPARES=>false]); }
}
class SpaceRepository {
 public function __construct(private PDO $pdo){}
 public function getAll():array{return $this->pdo->query("SELECT * FROM spaces ORDER BY name")->fetchAll();}
 public function findById(int $id):?array{$q=$this->pdo->prepare("SELECT * FROM spaces WHERE id=?");$q->execute([$id]);return $q->fetch()?:null;}
 public function create(string $n,int $c,float $p,string $d):void{$q=$this->pdo->prepare("INSERT INTO spaces(name,capacity,price,description) VALUES(?,?,?,?)");$q->execute([$n,$c,$p,$d]);}
 public function update(int $id,string $n,int $c,float $p,string $d):void{$q=$this->pdo->prepare("UPDATE spaces SET name=?,capacity=?,price=?,description=? WHERE id=?");$q->execute([$n,$c,$p,$d,$id]);}
 public function delete(int $id):bool{$q=$this->pdo->prepare("SELECT (SELECT COUNT(*) FROM reservations WHERE space_id=?)+(SELECT COUNT(*) FROM time_slots WHERE space_id=?)");$q->execute([$id,$id]);if((int)$q->fetchColumn())return false;$q=$this->pdo->prepare("DELETE FROM spaces WHERE id=?");$q->execute([$id]);return $q->rowCount()>0;}
}
class UserRepository {
 public function __construct(private PDO $pdo){}
 public function emailExists(string $e):bool{$q=$this->pdo->prepare("SELECT id FROM users WHERE email=?");$q->execute([$e]);return(bool)$q->fetchColumn();}
 public function createUser(string $n,string $e,string $h):void{$q=$this->pdo->prepare("INSERT INTO users(name,email,password,role,is_active) VALUES(?,?,?,'user',1)");$q->execute([$n,$e,$h]);}
 public function findByEmail(string $e):?array{$q=$this->pdo->prepare("SELECT id,name,email,password,role,is_active FROM users WHERE email=? LIMIT 1");$q->execute([$e]);return$q->fetch()?:null;}
 public function getAll():array{return$this->pdo->query("SELECT id,name,email,role,is_active,created_at FROM users ORDER BY created_at DESC")->fetchAll();}
 public function setActive(int $id,bool $a):void{$q=$this->pdo->prepare("UPDATE users SET is_active=? WHERE id=? AND role='user'");$q->execute([$a?1:0,$id]);}
}
class TimeSlotRepository {
 public function __construct(private PDO $pdo){}
 public function available(string $date):array{$q=$this->pdo->prepare("SELECT t.*,s.name space_name,s.price,s.description,GREATEST(t.capacity-SUM(CASE WHEN r.status IN ('pending','confirmed') THEN 1 ELSE 0 END),0) remaining FROM time_slots t JOIN spaces s ON s.id=t.space_id LEFT JOIN reservations r ON r.time_slot_id=t.id WHERE t.slot_date=? AND (t.slot_date>CURDATE() OR t.start_time>CURTIME()) GROUP BY t.id,s.name,s.price,s.description HAVING remaining>0 ORDER BY t.start_time,s.name");$q->execute([$date]);return$q->fetchAll();}
 public function find(int $id):?array{$q=$this->pdo->prepare("SELECT t.*,s.name space_name,s.price,s.capacity space_capacity,GREATEST(t.capacity-COALESCE(SUM(CASE WHEN r.status IN ('pending','confirmed') THEN 1 ELSE 0 END),0),0) remaining FROM time_slots t JOIN spaces s ON s.id=t.space_id LEFT JOIN reservations r ON r.time_slot_id=t.id WHERE t.id=? GROUP BY t.id,s.name,s.price,s.capacity");$q->execute([$id]);return$q->fetch()?:null;}
 public function all():array{return$this->pdo->query("SELECT t.*,s.name space_name,COALESCE(SUM(CASE WHEN r.status IN ('pending','confirmed') THEN 1 ELSE 0 END),0) active_count FROM time_slots t JOIN spaces s ON s.id=t.space_id LEFT JOIN reservations r ON r.time_slot_id=t.id GROUP BY t.id,s.name ORDER BY t.slot_date DESC,t.start_time DESC")->fetchAll();}
 public function save(?int $id,int $space,string $date,string $start,string $end,int $capacity):void{
  $d=DateTime::createFromFormat("Y-m-d",$date);if(!$d||$d->format("Y-m-d")!==$date||$date<date("Y-m-d"))throw new InvalidArgumentException("Choose today or a future date.");
  if(!preg_match("/^\d{2}:\d{2}$/",$start)||!preg_match("/^\d{2}:\d{2}$/",$end)||$start>=$end)throw new InvalidArgumentException("Enter a valid time range.");
  if($date===date("Y-m-d")&&$start<=date("H:i"))throw new InvalidArgumentException("The slot must start in the future.");
  if($capacity<1)throw new InvalidArgumentException("Capacity must be at least 1.");
  $q=$this->pdo->prepare("SELECT capacity FROM spaces WHERE id=?");$q->execute([$space]);$max=$q->fetchColumn();if($max===false||$capacity>(int)$max)throw new InvalidArgumentException("Slot capacity cannot exceed the space capacity.");
  if(!$id){$q=$this->pdo->prepare("INSERT INTO time_slots(space_id,slot_date,start_time,end_time,capacity) VALUES(?,?,?,?,?)");$q->execute([$space,$date,$start,$end,$capacity]);return;}
  $q=$this->pdo->prepare("SELECT t.*,COALESCE(SUM(CASE WHEN r.status IN ('pending','confirmed') THEN 1 ELSE 0 END),0) active_count FROM time_slots t LEFT JOIN reservations r ON r.time_slot_id=t.id WHERE t.id=? GROUP BY t.id");$q->execute([$id]);$old=$q->fetch();if(!$old)throw new RuntimeException("Time slot not found.");
  if($capacity<(int)$old["active_count"])throw new InvalidArgumentException("Capacity cannot be below active reservations.");
  $changed=(int)$old["space_id"]!==$space||$old["slot_date"]!==$date||$old["start_time"]!==$start||$old["end_time"]!==$end;
  if($changed&&(int)$old["active_count"]>0)throw new RuntimeException("A slot with active reservations cannot be rescheduled.");
  $q=$this->pdo->prepare("UPDATE time_slots SET space_id=?,slot_date=?,start_time=?,end_time=?,capacity=? WHERE id=?");$q->execute([$space,$date,$start,$end,$capacity,$id]);
 }
 public function deleteIfUnused(int $id):bool{$q=$this->pdo->prepare("SELECT COUNT(*) FROM reservations WHERE time_slot_id=? AND status IN ('pending','confirmed')");$q->execute([$id]);if((int)$q->fetchColumn())return false;$q=$this->pdo->prepare("DELETE FROM time_slots WHERE id=?");$q->execute([$id]);return$q->rowCount()>0;}
}
class ReservationRepository {
 public function __construct(private PDO $pdo){}
 public function forUser(int $id):array{$q=$this->pdo->prepare("SELECT r.*,s.name space_name FROM reservations r JOIN spaces s ON s.id=r.space_id WHERE r.user_id=? ORDER BY r.booking_date DESC,r.start_time DESC");$q->execute([$id]);return$q->fetchAll();}
 public function all(string $search="",string $status=""):array{$w=[];$p=[];if($search!==""){$w[]="(u.name LIKE ? OR u.email LIKE ?)";$p[]="%$search%";$p[]="%$search%";}if(in_array($status,["pending","confirmed","rejected","cancelled"],true)){$w[]="r.status=?";$p[]=$status;}$sql="SELECT r.*,u.name user_name,u.email user_email,s.name space_name FROM reservations r JOIN users u ON u.id=r.user_id JOIN spaces s ON s.id=r.space_id";if($w)$sql.=" WHERE ".implode(" AND ",$w);$sql.=" ORDER BY r.booking_date DESC,r.start_time DESC";$q=$this->pdo->prepare($sql);$q->execute($p);return$q->fetchAll();}
 public function statistics():array{$r=$this->pdo->query("SELECT COUNT(*) total,SUM(status='pending') pending,SUM(status='confirmed') confirmed,SUM(status='rejected') rejected,SUM(status='cancelled') cancelled FROM reservations")->fetch();$r["users"]=(int)$this->pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();foreach(["total","pending","confirmed","rejected","cancelled","users"] as $k)$r[$k]=(int)($r[$k]??0);return$r;}
 public function createForSlot(int $user,int $slot,string $note):void{$this->pdo->beginTransaction();try{$q=$this->pdo->prepare("SELECT * FROM time_slots WHERE id=? FOR UPDATE");$q->execute([$slot]);$t=$q->fetch();if(!$t)throw new RuntimeException("This time slot is no longer available.");if($t["slot_date"]<date("Y-m-d")||($t["slot_date"]===date("Y-m-d")&&$t["start_time"]<=date("H:i:s")))throw new RuntimeException("This time slot has already started.");$q=$this->pdo->prepare("SELECT id FROM reservations WHERE user_id=? AND time_slot_id=? LIMIT 1");$q->execute([$user,$slot]);if($q->fetchColumn())throw new RuntimeException("You have already reserved this slot.");$q=$this->pdo->prepare("SELECT COUNT(*) FROM reservations WHERE time_slot_id=? AND status IN ('pending','confirmed')");$q->execute([$slot]);if((int)$q->fetchColumn()>=(int)$t["capacity"])throw new RuntimeException("This time slot is full.");$q=$this->pdo->prepare("INSERT INTO reservations(user_id,space_id,time_slot_id,booking_date,start_time,end_time,note,status) VALUES(?,?,?,?,?,?,?,'pending')");$q->execute([$user,$t["space_id"],$slot,$t["slot_date"],$t["start_time"],$t["end_time"],$note]);$this->pdo->commit();}catch(Throwable $e){if($this->pdo->inTransaction())$this->pdo->rollBack();if($e instanceof PDOException&&$e->getCode()==="23000")throw new RuntimeException("You have already reserved this slot.");throw$e;}}
 public function cancelOwned(int $id,int $user):bool{$q=$this->pdo->prepare("UPDATE reservations SET status='cancelled' WHERE id=? AND user_id=? AND status IN ('pending','confirmed') AND (booking_date>CURDATE() OR (booking_date=CURDATE() AND start_time>CURTIME()))");$q->execute([$id,$user]);return$q->rowCount()>0;}
 public function setAdminStatus(int $id,string $status):bool{if(!in_array($status,["confirmed","rejected","cancelled"],true))return false;$from=$status==="cancelled"?"('pending','confirmed')":"('pending')";$q=$this->pdo->prepare("UPDATE reservations SET status=? WHERE id=? AND status IN $from");$q->execute([$status,$id]);return$q->rowCount()>0;}
}
function requireAdmin():void{if(session_status()!==PHP_SESSION_ACTIVE)session_start();if(($_SESSION["role"]??"")!=="admin"||empty($_SESSION["user_id"])){header("Location: ../login.php");exit;}}
function requireUser():void{if(session_status()!==PHP_SESSION_ACTIVE)session_start();if(($_SESSION["role"]??"")!=="user"||empty($_SESSION["user_id"])){header("Location: login.php");exit;}global $pdo;$q=$pdo->prepare("SELECT is_active FROM users WHERE id=?");$q->execute([(int)$_SESSION["user_id"]]);if((int)$q->fetchColumn()!==1){$_SESSION=[];session_destroy();header("Location: login.php?deactivated=1");exit;}}
try{$database=new Database();$pdo=$database->connect();}catch(PDOException $e){die("Database connection failed.");}
