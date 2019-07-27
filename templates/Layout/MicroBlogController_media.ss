<div class="MicroblogMedia">
    <div class="row">
        <div class="col-md-8">
            <div class="MicroblogMedia__Item">
                <% if $IsImage %>
                $Item
                <% end_if %>
            </div>
        </div>
        <div class="col-md-4">
            <div class="Microblog" data-microblog-settings='$Settings.JSON'></div>
        </div>
    </div>
</div>