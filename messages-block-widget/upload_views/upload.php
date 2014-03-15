<!-- The template to display files available for upload -->
<script id="template-upload" type="text/x-tmpl">
    {% for (var i=0, file; file=o.files[i]; i++) { %}
    <li class="template-upload fade">
        {% if (file.error) { %}
        <div class="file-container error t-error" title="{%=locale.fileupload.errors[file.error] || file.error%}">
        </div>
        {% } else { %}
        <div class="file-container view">
            <div class="spacer"></div>
            <div class="load-container">
                <div class="progress progress-success active"><div class="bar" style="width:0%;"></div></div>
            </div>
            
        </div>
        {% } %}

        <div class="cancel">
            {% if (!i) { %}
            <button class="btn btn-warning">
                <span class="icon iconl-close"></span>
            </button>
            {% } %}
        </div>
    </li>
    {% } %}
</script>
