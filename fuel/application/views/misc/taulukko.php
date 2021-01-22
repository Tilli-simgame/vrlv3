    <h1><?php echo fuel_var('title', '')?></h1>
    <?php echo fuel_var('text_view', '')?>
    
    <?php
        if (isset($massatuho)){
            echo $massatuho['form'];
            echo $massatuho['instructions'];
            echo $massatuho['buttons'];
   
        }
    ?>
    
    <div id="result_div"></div>
    
    <?php if(!empty($headers)) : ?>
        <script>
            function formatValue(value, type) {                
                if (type === 'date') {
                    return moment(value).format('DD.MM.YYYY');
                }
                
                if (type === 'VH') {
                    return 'VH' + value.slice(0, 2) + '-' + value.slice(2, 5) + '-' + value.slice(5, 9);
                }
                
                
                if (type === 'small'){
                    return '<small>'+value+'</small>';
                }
                
                if (type === 'bool'){
                    if(value == '1'){
                        return "kyllä";
                    }else {
                        return "ei";
                    }
                }
                return value;
            }
        
            $(document).ready(function() {
                var headers = <?=$headers?>;
                var data = <?=$data?>;
                var numcolumns = 0;
                var table = "<table id='result_table'><thead><tr>";
                var prepend_text;
                var output;

                for(var i in headers)
                {
                    table += "<th>" + headers[i].title + "</th>";
                    numcolumns++;
                }
                
                table += "</tr></thead><tbody>";
                
                for(d in data)
                {
                    if (data[d] == null)
                        continue;
                    
                    table += "<tr>";
                    
                    for(var i=1; i<=numcolumns; i++)
                    {
                        if (headers[i].prepend_text != undefined)
                            prepend_text = headers[i].prepend_text;
                        else
                            prepend_text = "";
                        
                        //Jos pitää koota kaikki saman hakukohteen useat arvot yhteen arvoon, mennään iffiin
                        //esim. tallihaussa kootaan yhteen muuttujaan kaikki tallin kategoriat
                        if(headers[i].aggregated_by != undefined)
                        {
                            for(var agr in data) //etsitään muut aggregoitavat rivit
                            {
                                if (data[agr] == null)
                                    continue;
                                
                                //eihän ole sama rivi kuin mitä tulostetaan && vastaahan yksilöivät ID:t toisiaan (aggregated_by:t siis kertovat ID:n keyn)
                                if (d != agr && data[agr][headers[i].aggregated_by] == data[d][headers[i].aggregated_by])
                                {
                                    data[d][headers[i].key] += ", " + data[agr][headers[i].key]; //appendaus
                                    data[agr] = null; //poistetaan aggregoitu rivi ettei myöhemmin tulosteta sitä uudestaan
                                }
                            }
                        }
                        
                        if (headers[i].image != undefined) {
                            output = "<img src='" + headers[i].image + "'/>";
                        } else if (headers[i].static_text != undefined) {
                            output = headers[i].static_text;
                        }else if (headers[i].checkbox_id != undefined){
                            output = "<input type=\"checkbox\" name=\"" + headers[i].checkbox_id +"\" value=\"" + data[d][headers[i].key] +"\" >";
                        } else {
                            output = prepend_text + formatValue(data[d][headers[i].key], headers[i].type);
                        }
                        
                        if (headers[i].key_link != undefined) {
                            table += "<td><a href='" + headers[i].key_link + formatValue(data[d][headers[i].key], headers[i].type) + "'>" + output + "</a></td>"; //profiili linkatun arvon tulostus soluunsa
                        } else if (headers[i].type === 'url') {
                            table += "<td><a href='" + formatValue(data[d][headers[i].key], headers[i].type) + "' target='_blank'>" + output + "</a></td>"; //profiili linkatun arvon tulostus soluunsa
                        }
                        else {
                            table += "<td>" + output + "</td>"; //normaali arvon tulostus soluunsa
                        }
                    }

                    table += "</tr>";
                }
                
                table += "<tbody></table>";
                
                
                
                $("#result_div").append(table);
                
                    
                $.fn.dataTable.moment( 'DD.MM.YYYY' );
                $.fn.dataTable.moment( 'DD.MM.YYYY' );
                
                $('#result_table').DataTable({
                    "order": [[ 0, "desc" ]],
                    "lengthMenu": [ 50, 100, 150, 200 ],
                    "pageLength": 50

                    });
                
                
                
                
            });



        </script>
    <?php endif; ?>
</p>

    <?php
        if (isset($massatuho)){
           echo "</form>";
   
        }
    ?>