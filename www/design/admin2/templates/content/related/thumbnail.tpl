{* DO NOT EDIT THIS FILE! Use an override template instead. *}

<div class="image-thumbnail-item">

    {attribute_view_gui attribute=$related_object.data_map.image image_class=small}

    <p class="checkbox"><input type="checkbox" id="related-object-id-{$related_object.id}" name="DeleteRelationIDArray[]" value="{$related_object.id}" /></p>
    <p>{$related_object.name|wash}</p>

    <input class="linkbox" type="text" value="&lt;object id={$related_object.id} /&gt;">

</div>