<?php 
                    $sqlGetProduct = ' select name, 
                                              commercial_description cm, 
                                              valid_from vf, 
                                              valid_to vt, 
                                              status prod_status, 
                                              changed_when cw, 
                                              changed_by cb, 
                                              gtc_document_id gtc, 
                                              ipid_document_id ipid, 
                                              logo_id logo 
                                                    from product';
                    $result = $connection->query($sqlGetProduct);
                    if($result->num_rows > 0){
                        while($row = $result->fetch_assoc()) {
                            $productStatus = $row['status'];
                            echo 
                            '<div class="form-group">
                                <label class="col-md-4 control-label" for="product_name">Product name</label>  
                                <div class="col-md-4">
                                    <input id="product_name" name="product_name" class="form-control input-md" type="text" value='.$row['name'].'> 
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="col-md-4 control-label" for="product_cd">Commercial Description</label>  
                                <div class="col-md-4">
                                    <input id="product_cd" name="product_cd" class="form-control input-md" type="text" value="'.$row['cm'].'"> 
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="col-md-4 control-label" for="product_valid_from">Valid From</label>
                                <div class="col-md-4">
                                    <input id="product_valid_from" name="product_valid_from"  class="form-control input-md" type="date" value='.$row['vf'].'> 
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="col-md-4 control-label" for="product_valid_to">Valid To</label>
                                <div class="col-md-4">
                                    <input id="product_valid_to" name="product_valid_to" class="form-control input-md" type="date" value='.$row['vt'].'> 
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="col-md-4 control-label" for="product_status">Product Status</label>
                                <div class="col-md-4">
                                    <select id="product_status" name="product_status" class="form-control">';
                                    if($row['prod_status'] == 'Active'){
                                        echo 
                                        '<option selected>Active</option>
                                         <option>Retired</option>
                                    </select>
                                </div>
                            </div>'; 
                                    } else {
                                        echo 
                                        '<option>Active</option>
                                         <option selected>Retired</option>
                                    </select>
                                </div>
                            </div>';}
                            echo ' 
                            <div class="form-group">
                                <label class="col-md-4 control-label" for="product_changed_when">Changed When</label>
                                <div class="col-md-4">
                                    <input id="" name="product_changed_when" class="form-control" type="text" value="'.$row['cw'].'" disabled>
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="col-md-4 control-label" for="product_changed_by">Changed By</label>
                                <div class="col-md-4">
                                    <input id="" name="product_changed_by" class="form-control" type="text" value="'.$row['cb'].'" disabled>
                                </div>
                            </div>
                            <div class="form-group">
                                <a class="col-md-4 control-label" id="product_documents">Documents*</a>
                            </div>
                            <div class="form-group">
                                <label class="col-md-4 control-label"></label>
                                <div class="col-md-4">
                                    <button id="btn_update_product" type="submit">Update</button>
                                    <div class="loadingSymbol"></div>
                                </div>
                            </div>
            </fieldset>
        </form>';
                        }
                    }
    echo '<div id="documentsModal" class="modal fade" role="dialog">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <button type="button" class="close" data-dismiss="modal">&times;</button>
                        <h4 class="modal-title">Product Documents</h4>
                        <div class="loadingSymbol"></div>
                        <span id="product_file_upload_msg"></span>
                    </div>
                    <div class="modal-body">
                        <form id="form_gtc" method="post" enctype="multipart/form-data"> 
                            <p><label>General Terms & Conditions</label>
                                <a href="downloadProductDoc?name=gtc">Download</a>
                                <input id="product_gtc_file" type="file" name="gtc" />
                            </p>
                        </form>
                        <form id="form_ipid" method="post" enctype="multipart/form-data">
                            <p><label>Insurance Product Information Document</label>
                                <a href="downloadProductDoc?name=ipid">Download</a>
                                <input id="product_ipid_file" type="file" name="ipid"/>
                            </p>
                        </form>
                        <form id="form_logo" method="post" enctype="multipart/form-data">
                            <p><label>Logo</label>
                                <a href="downloadProductDoc?name=logo">Download</a>
                                <input id="product_logo_file" type="file" name="logo"/><br>
                                <img id="productLogo" src="#" alt="logo"/>
                            </p>
                        </form>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                    </div>
                </div>
            </div>
        </div>';?>