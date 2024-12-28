<?php if($_settings->chk_flashdata('success')): ?>
<script>
	alert_toast("<?php echo $_settings->flashdata('success') ?>",'success')
</script>
<?php endif;?>
<?php 
$meta_qry=$conn->query("SELECT * FROM employee_meta where user_id = '{$_settings->userdata('id')}' and meta_field = 'approver' ");
$is_approver = $meta_qry->num_rows > 0 && $meta_qry->fetch_array()['meta_value'] == 'on' ? true : false;
?>
<div class="card card-outline card-primary">
	<div class="card-header">
		<h3 class="card-title">Danh sách đơn xin</h3>
		<div class="card-tools">
			<a href="?page=leave_applications/manage_application" class="btn btn-flat btn-primary"><span class="fas fa-plus"></span>  Create New</a>
		</div>
	</div>
	<div class="card-body">
		<div class="container-fluid">
        <div class="container-fluid">
			<table class="table table-hover table-stripped">
				<?php if($_settings->userdata('type') != 3): ?>
				<colgroup>
					<col width="10%">
					<col width="25%">
					<col width="25%">
					<col width="15%">
					<col width="15%">
					<col width="10%">
				</colgroup>
				<?php else: ?>
					<colgroup>
						<col width="10%">
						<col width="50%">
						<col width="15%">
						<col width="15%">
						<col width="10%">
					</colgroup>
				<?php endif; ?>
				<thead>
					<tr>
						<th>#</th>
						<?php if($_settings->userdata('type') != 3): ?>
						<th>Tên</th>
						<?php endif; ?>
						<th>Loại nghỉ phép</th>
						<th>Số ngày nghỉ</th>
						<th>Trạng thái</th>
						<th>Action</th>
					</tr>
				</thead>
				<tbody>
					<?php 
					$i = 1;
						$where = '';
						if($_settings->userdata('type') == 3)
						$where = " and u.id = '{$_settings->userdata('id')}' ";
						$qry = $conn->query("SELECT l.*,concat(u.lastname,', ',u.firstname,' ',u.middlename) as `name`,lt.code,lt.name as lname from `leave_applications` l inner join `users` u on l.user_id=u.id inner join `leave_types` lt on lt.id = l.leave_type_id where (date_format(l.date_start,'%Y') = '".date("Y")."' or date_format(l.date_end,'%Y') = '".date("Y")."') {$where} order by FIELD(l.status,0,1,2,3), unix_timestamp(l.date_created) desc ");
						while($row = $qry->fetch_assoc()):
							$lt_qry = $conn->query("SELECT meta_value FROM `employee_meta` where user_id = '{$row['user_id']}' and meta_field = 'employee_id' ");
							$row['employee_id'] = ($lt_qry->num_rows > 0) ? $lt_qry->fetch_array()['meta_value'] : "N/A";
					?>
						<tr>
							<td class="text-center"><?php echo $i++; ?></td>
							<?php if($_settings->userdata('type') != 3): ?>
							<th>
								<small><b>ID: </b><?php echo $row['employee_id'] ?></small><br>
								<small><b>Name: </b><?php echo $row['name'] ?></small>
							</th>
							<?php endif; ?>
							<td><?php echo $row['code'] . ' - '. $row['lname'] ?></td>
							<td><?php echo $row['leave_days'] ?></td>
							<td class="text-center">
								<?php if($row['status'] == 1): ?>
									<span class="badge badge-success">Chấp thuận</span>
								<?php elseif($row['status'] == 2): ?>
									<span class="badge badge-danger">Từ chối</span>
								<?php elseif($row['status'] == 3): ?>
									<span class="badge badge-danger">Hủy</span>
								<?php else: ?>
									<span class="badge badge-primary">Chờ xử lý</span>
								<?php endif; ?>
							</td>
							<td align="center">
								 <button type="button" class="btn btn-flat btn-default btn-sm dropdown-toggle dropdown-icon" data-toggle="dropdown">
				                  		Action
				                    <span class="sr-only">Toggle Dropdown</span>
				                  </button>
				                  <div class="dropdown-menu" role="menu">
								  	<a class="dropdown-item view_application" href="javascript:void(0)" data-id="<?php echo $row['id'] ?>"><span class="fa fa-eye"></span>Xem</a>
				                    <div class="dropdown-divider"></div>
									<?php if($_settings->userdata('type') != 3): ?>
				                    <a class="dropdown-item update_status" href="javascript:void(0)" data-id="<?php echo $row['id'] ?>"><span class="fa fa-check-square"></span>Cập nhật trạng thái</a>
				                    <div class="dropdown-divider"></div>
									<?php endif; ?>
									<?php if($_settings->userdata('type') != 3 || ($row['status'] == '0') ): ?>
				                    <a class="dropdown-item" href="?page=leave_applications/manage_application&id=<?php echo $row['id'] ?>"><span class="fa fa-edit text-primary"></span> Sửa</a>
				                    <div class="dropdown-divider"></div>
				                    <a class="dropdown-item delete_data" href="javascript:void(0)" data-id="<?php echo $row['id'] ?>"><span class="fa fa-trash text-danger"></span> Xóa</a>
									<?php endif; ?>
				                  </div>
							</td>
						</tr>
					<?php endwhile; ?>
				</tbody>
			</table>
		</div>
		</div>
	</div>
</div>
<script>
	$(document).ready(function(){
		$('.delete_data').click(function(){
			_conf("Bạn có chắc muốn xóa không ?","delete_leave_application",[$(this).attr('data-id')])
		})
		$('.view_application').click(function(){
			uni_modal("<i class='fa fa-list'></i> Chi tiết đơn xin nghỉ","leave_applications/view_application.php?id="+$(this).attr('data-id'))
		})
		
		$('.update_status').click(function(){
			uni_modal("<i class='fa fa-check-square'></i> Cập nhật trạng thái","leave_applications/update_status.php?id="+$(this).attr('data-id'))
		})
		$('.table').dataTable({
			columnDefs: [
				{ orderable: false, targets: [3,4] }
			]
		});
	})
	function delete_leave_application($id){
		start_loader();
		$.ajax({
			url:_base_url_+"classes/Master.php?f=delete_leave_application",
			method:"POST",
			data:{id: $id},
			dataType:"json",
			error:err=>{
				console.log(err)
				alert_toast("An error occured.",'error');
				end_loader();
			},
			success:function(resp){
				if(typeof resp== 'object' && resp.status == 'success'){
					location.reload();
				}else{
					alert_toast("An error occured.",'error');
					end_loader();
				}
			}
		})
	}
</script>