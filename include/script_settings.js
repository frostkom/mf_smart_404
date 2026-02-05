jQuery(function($)
{
	var dom_obj_table = $(".smart_404_list"),
		dom_obj_form = dom_obj_table.siblings(".mf_form"),
		dom_form_from = dom_obj_form.find(".form_textfield:first-of-type input"),
		dom_form_to = dom_obj_form.find(".form_textfield:last-of-type input");

	dom_obj_table.on('click', ".fa-edit, .fa-wrench", function(e)
	{
		var dom_button = $(e.currentTarget),
			dom_data_from = dom_button.data('from'),
			dom_data_to = (dom_button.data('to') || '');

		dom_form_from.val(dom_data_from);
		dom_form_to.val(dom_data_to);

		jQuery("html, body").animate({scrollTop: (dom_obj_form.offset().top - 40)}, 800);

		return false;
	});

	dom_obj_table.on('click', ".fa-trash, .fa-eye-slash", function(e)
	{
		var dom_button = $(e.currentTarget),
			dom_td = dom_button.parent("td"),
			dom_tr = dom_td.parent("tr");

		dom_td.html("<i class='fa fa-spinner fa-spin'></i>");

		$.ajax(
		{
			url: script_smart_404_settings.ajax_url,
			type: 'post',
			dataType: 'json',
			data: {
				action: dom_button.attr('rel'),
				redirect_id: dom_tr.attr('id').replace("redirect_", "")
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

	dom_obj_form.on('click', "button[name=btnRedirectSave]", function(e)
	{
		var dom_button = $(e.currentTarget),s
			dom_result = dom_button.siblings("p");

		dom_result.html("<i class='fa fa-spinner fa-spin fa-2x'></i>");

		$.ajax(
		{
			url: script_smart_404_settings.ajax_url,
			type: 'post',
			dataType: 'json',
			data: {
				action: 'api_smart_404_save_redirect',
				redirect_from: dom_form_from.val(),
				redirect_to: dom_form_to.val()
			},
			success: function(data)
			{
				dom_result.empty();

				if(data.success)
				{
					dom_form_from.val('');
					dom_form_to.val('');

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
});