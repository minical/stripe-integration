<div class="page-header">
	<h2><?php echo l('payment_gateway_settings'); ?></h2>
</div>

    <?php if($this->session->flashdata('setting_update')){ ?>
        <div class="alert alert-success">
            <?php echo $this->session->flashdata('setting_update'); ?>
        </div>
    <?php } //$this->session->unset_flashdata('setting_update'); ?>

<div class="form-horizontal" style="min-height: 165px;">
	<div class="form-group">
		<label for="current_time" class="col-sm-3 control-label">
			<?php echo l('current_payment_gateway'); ?>
		</label>
		<div class="col-sm-9">
			<select name="payment_gateway" class="form-control">
				
			</select>	
		</div>
	</div>

	<div id="form-div">
	</div>

	<div class="col-sm-12 text-center">
		<div class="btn btn-light" id="update-button"><?php echo l('Update', true); ?></div>
	</div>
</div>	
