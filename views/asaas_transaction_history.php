
<div class="app-page-title">
	<div class="page-title-wrapper">
		<div class="page-title-heading">
			<div class="page-title-icon">
				<i class="pe-7s-notebook text-success"></i>
			</div>
			<div><?php echo l('Hoteli.pay', true); ?>

			</div>
		</div>
	</div>
</div>


<div class="main-card card">
	<div class="card-body">

		<div class="container">
		  	<div class="row">
			    <div class="col-sm-3">
			    	<b>Hoteli.pay</b>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; <b><?php echo $this->company_name; ?></b>
			    </div>
				<div class="col-sm-9">
					<table class="table table-bordered" style="width: 75%;">
					    <thead>
					      	<tr>
						        <th>Date</th>
						        <th>Booking</th>
						        <th>Client</th>
						        <th>Amount</th>
						        <th>Method</th>
					      	</tr>
				    	</thead>
			    		<tbody>
				    		<?php if(isset($transactions) && count($transactions) > 0):
				    		$total_balance = 0;
				    		foreach($transactions as $pay):
				    		$customer = $this->processpayment->get_customer_name($pay['customer']);
				    		$booking = $this->processpayment->get_booking($pay['id']);
				    		 ?>
					      		<tr>
							        <td><?php echo $pay['dateCreated']; ?></td>
							        <td><a href="<?php echo base_url().'invoice/show_invoice/'.$booking['booking_id']; ?>" target="_blank"><?php echo $booking['booking_id']; ?></a></td>
							        <td><?php echo isset($pay['paymentLink']) && $pay['paymentLink'] ? "" : $customer['customer_name']; ?></td>
							        <td><?php echo isset($pay['paymentLink']) && $pay['paymentLink'] ? number_format((abs($pay['value'])), 2, ".", ",") : number_format((abs($pay['value']) / 100), 2, ".", ","); ?></td>
							        <td><?php echo $pay['billingType']; ?></td>
					      		</tr>
					      		<?php $total_balance += isset($pay['paymentLink']) && $pay['paymentLink'] ? (abs($pay['value'])) : (abs($pay['value']) / 100); ?>
				      		<?php endforeach;
				      		else: ?>
				      			<tr>
				      				<td>No transaction found.</td>
				      			</tr>
				      		<?php endif; ?>
    					</tbody>
  					</table>
				</div>
				<div class="col-sm-3">
			    	<b>Balance :</b>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; <b><?php echo number_format($total_balance, 2, ".", ","); ?></b>
				</div>
		  	</div>
		</div>
	</div>
</div>