document.addEventListener('DOMContentLoaded', function () {

	(function Refund(){

		this.$root = document.getElementById('woocommerce-order-items');
		this.$manualRefundBtn = null;
		
		this.init = function() {
			if( !this.$root || this.$root.length === 0 ) return;
			this.$manualRefundBtn = this.$root.getElementsByClassName('do-manual-refund')[0];
			
			if(this.$manualRefundBtn) {
				this.$manualRefundBtn.classList.remove('button-primary');
			}
		};

		return this.init();
	})();
});