<!-- The template to display files available for download -->
<script id="template-download" type="text/x-tmpl">
    {% for (var i=0, file; file=o.files[i]; i++) { %}
    <li class="template-download fade">
        {% if (file.error) { %}
        <div class="file-container error">
            <span class="label label-important">{%=locale.fileupload.error%}</span> {%=locale.fileupload.errors[file.error] || file.error%}
        </div>
        {% } else { %}
        <div class="file-container view">
            <div class="preview">
                {% if (file.thumbnail_url) { %}
                <a href="{%=file.url%}" title="{%=file.name%}" rel="gallery" download="{%=file.name%}"><img src="{%=file.thumbnail_url%}"></a>
                {% } %}
            </div>
        </div>
        {% } %}
        <div class="delete">
            <button class="btn btn-danger" data-type="{%=file.delete_type%}" data-url="{%=file.delete_url%}">
                <span class="icon iconl-close"></span>
            </button>
        </div>
    </li>
    {% } %}
</script>
