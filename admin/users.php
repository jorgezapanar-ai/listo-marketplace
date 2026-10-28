<?php
declare(strict_types=1);

require dirname(__DIR__) . '/app/bootstrap.php';
$user=require_role('admin');
$role=(string)($_GET['role']??'');$status=(string)($_GET['status']??'');$q=trim((string)($_GET['q']??''));$page=max(1,(int)($_GET['page']??1));$perPage=20;
$where=['1=1'];$params=[];
if(in_array($role,['client','provider','admin'],true)){$where[]='u.role=?';$params[]=$role;}
if(in_array($status,['active','suspended'],true)){$where[]='u.status=?';$params[]=$status;}
if($q!==''){$where[]='(u.full_name LIKE ? OR u.phone LIKE ? OR u.email LIKE ?)';$term='%'.$q.'%';array_push($params,$term,$term,$term);}
$whereSql=implode(' AND ',$where);$count=db()->prepare("SELECT COUNT(*) FROM users u WHERE $whereSql");$count->execute($params);$total=(int)$count->fetchColumn();$pages=max(1,(int)ceil($total/$perPage));$page=min($page,$pages);
$statement=db()->prepare("SELECT u.id,u.full_name,u.phone,u.email,u.role,u.status,u.created_at,u.last_login_at,pp.verification_status FROM users u LEFT JOIN provider_profiles pp ON pp.user_id=u.id WHERE $whereSql ORDER BY u.created_at DESC LIMIT $perPage OFFSET ".(($page-1)*$perPage));$statement->execute($params);$users=$statement->fetchAll();
$pageTitle='Usuarios';$pageSubtitle='Clientes, proveedores y administradores';$activeDashboard='users';require dirname(__DIR__).'/templates/dashboard-header.php';
?>
<div class="page-head"><div><h2>Gestión de usuarios</h2><p><?=$total?> cuenta<?=$total===1?'':'s'?></p></div></div>
<form class="filter-bar" method="get"><div class="field"><label for="q">Buscar</label><input id="q" name="q" value="<?=e($q)?>" placeholder="Nombre, celular o correo"></div><div class="field"><label for="role">Rol</label><select id="role" name="role"><option value="">Todos</option><option value="client"<?=selected($role,'client')?>>Clientes</option><option value="provider"<?=selected($role,'provider')?>>Proveedores</option><option value="admin"<?=selected($role,'admin')?>>Administradores</option></select></div><button class="btn btn-primary">Buscar</button></form>
<section class="panel"><?php if($users===[]):?><div class="empty-state"><span class="empty-icon"><?=icon('user')?></span><h3>Sin resultados</h3><p>No encontramos usuarios con esos filtros.</p></div><?php else:?><div class="table-scroll"><table class="data-table"><thead><tr><th>Usuario</th><th>Contacto</th><th>Rol</th><th>Cuenta</th><th>Validación</th><th>Alta</th><th></th></tr></thead><tbody><?php foreach($users as $row):?><tr><td class="table-title"><strong><?=e($row['full_name'])?></strong><span>#<?=(int)$row['id']?></span></td><td><?=e($row['phone'])?><br><span class="muted"><?=e($row['email']??'Sin correo')?></span></td><td><?=e(['client'=>'Cliente','provider'=>'Proveedor','admin'=>'Admin'][$row['role']])?></td><td><span class="status status-<?=e(status_class($row['status']))?>"><?=e(status_label($row['status']))?></span></td><td><?php if($row['role']==='provider'):?><span class="status status-<?=e(status_class($row['verification_status']))?>"><?=e(status_label($row['verification_status']))?></span><?php else:?>—<?php endif;?></td><td><?=e(date_pe($row['created_at']))?></td><td><a class="btn btn-secondary btn-small" href="<?=e(url('admin/user.php?id='.(int)$row['id']))?>">Revisar</a></td></tr><?php endforeach;?></tbody></table></div><?php endif;?></section>
<?php if($pages>1):?><nav class="pagination"><?php for($i=1;$i<=$pages;$i++):?><a class="<?=$i===$page?'active':''?>" href="?q=<?=e(urlencode($q))?>&amp;role=<?=e($role)?>&amp;status=<?=e($status)?>&amp;page=<?=$i?>"><?=$i?></a><?php endfor;?></nav><?php endif;?>
<?php require dirname(__DIR__).'/templates/dashboard-footer.php';?>
