/**
 * CSVのアップロードで利用するjs
------------------------------*/

//メディァアップローダーの起動
function add_csv() {
	jQuery(function(){
		
		//メディアアップロードの起動
	    var upload_add_csv;
	    jQuery('.js-add-csv').on('click',function(e) {
		    var _this = jQuery(this);
	        e.preventDefault();
	        if (upload_add_csv) {
	            upload_add_csv.open();
	            return;
	        }

	        //メディアアップローダーの設定
			upload_add_csv = wp.media({
	            title: "CSVの選択",
	            library: { type: "text" },
	            button: { text: "CSVの選択" },
	            multiple: false
            });
			
	        //画像選択後の処理
	        upload_add_csv.on("select", function() {
	            var images = upload_add_csv.state().get("selection");
	            images.each(function(file){ 
					var filePath = file.attributes.url.replace(content_path, '');
					update_inpiut(filePath, _this);
	            });
			});
			upload_add_csv.open();
	    });
	});
}

//inputに選択したCSVのpathを入れる
function update_inpiut(filePath, _this) {
	_this.prev('input[name="import"]').val(filePath);
}

//実行中のGIFを表示させる
function execution_button() {
	jQuery(function() {
		jQuery('form .submit button[type="submit"]').on('click', function() {
			var import_val = jQuery('input[name="import"]').val();
			if (import_val) {
				jQuery(this).hide();
				jQuery(this).next('img.load').show();
			}
		});
	});
}