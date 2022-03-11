<div id="printable-container">
	<div class="app-page-title">
   <div class="page-title-wrapper">
        <div class="page-title-heading">
            <div class="page-title-icon">
                <i class="pe-7s-notebook text-success"></i>
            </div>
			<?php echo l('stripe-integration/payment gateway settings'); ?>
        </div>
    </div>
</div>


<div class="main-card card">
    <div class="card-body">
    <?php if($this->session->flashdata('setting_update')){ ?>
        <div class="alert alert-success">
            <?php echo $this->session->flashdata('setting_update'); ?>
        </div>
    <?php } //$this->session->unset_flashdata('setting_update'); ?>

<div class="form-horizontal" style="min-height: 165px;">
	<div class="form-group">
		<label for="current_time" class="col-sm-3 control-label">
			<?php echo l('stripe-integration/Current Payment Gateway'); ?>
		</label>
		<div class="col-sm-9">
			<select name="payment_gateway" class="form-control">
				
			</select>	
		</div>
	</div>

	<div id="form-div">
	</div>

	<div class="col-sm-12 text-center">
		<div class="btn btn-light" id="update-button"><?php echo l('stripe-integration/update'); ?></div>
	</div>
</div>	

	</div>
	</div>