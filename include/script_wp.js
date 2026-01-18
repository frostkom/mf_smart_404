jQuery(function($)
{
	$(document).on('click', "button[name=btnRedirectSave]", function(e)
	{
		var dom_button = $(e.currentTarget),
			dom_redirect_from = dom_button.siblings(".mf_form").find(".form_textfield:first-of-type input"),
			dom_redirect_to = dom_button.siblings(".mf_form").find(".form_textfield:last-of-type input"),
			dom_result = dom_button.siblings("p");

		dom_result.html("<i class='fa fa-spinner fa-spin fa-2x'></i>");

		$.ajax(
		{
			url: script_smart_404.ajax_url,
			type: 'post',
			dataType: 'json',
			data: {
				action: 'api_smart_404_save_redirect',
				redirect_from: dom_redirect_from.val(),
				redirect_to: dom_redirect_to.val()
			},
			success: function(data)
			{
				dom_result.empty();

				if(data.success)
				{
					dom_redirect_from.val('');
					dom_redirect_to.val('');

					dom_result.html(data.message);
				}

				else
				{
					dom_result.html(data.message);
				}
			}
		});

		return false;
	});

	$(document).on('click', ".smart_404_list .fa-edit", function(e)
	{
		var dom_button = $(e.currentTarget),
			dom_data_from = dom_button.data('from'),
			dom_form = dom_button.parents(".smart_404_list").siblings(".mf_form"),
			dom_form_from = dom_form.find(".form_textfield:first-of-type input");

		dom_form_from.val(dom_data_from);

		return false;
	});

	$(document).on('click', ".smart_404_list .fa-trash", function(e)
	{
		var dom_button = $(e.currentTarget),
			dom_td = dom_button.parent("td"),
			dom_tr = dom_td.parent("tr"),
			dom_tr_id = dom_tr.attr('id').replace("redirect_", "");

		dom_td.html("<i class='fa fa-spinner fa-spin'></i>");

		$.ajax(
		{
			url: script_smart_404.ajax_url,
			type: 'post',
			dataType: 'json',
			data: {
				action: 'api_smart_404_remove_redirect',
				redirect_id: dom_tr_id
			},
			success: function(data)
			{
				if(data.success)
				{
					dom_tr.remove();
				}

				else
				{
					dom_td.html(data.message);
				}
			}
		});

		return false;
	});
});