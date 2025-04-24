<?php
include_once '../path.php';
include_once '../includes/functions.php';
if(isset( $_SERVER['HTTP_X_REQUESTED_WITH'] ) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest') { } else { include '../header.php'; }
?>
<style>
#quiz {
    margin-top: 4rem;
}  
#quiz .quiz-q {
    position: relative;
    padding: 2rem;
    width: 100%;
    background: var(--content-box-bg);
    border-radius: 10px;
    margin-top: 2rem;
    display: none;
}
#quiz .quiz-q.current {
    display: block;
    margin-top: 3rem;
}
#quiz .quiz-q.show-all {
    display: block;
}
#quiz .quiz-q.item-incorrect {

}
#quiz .quiz-q.item-incorrect:before {
    position: absolute;
    z-index: 2;
    bottom: 0;
    right: 0;
    content: "";
    height: 0;
    width: 0;
    border: 35px solid;
    border-color: transparent #ff5c61 #ff5c61 transparent;
    border-bottom-right-radius: 10px;
}
#quiz .quiz-q.item-incorrect:after {
    line-height: 1.4;
    position: absolute;
    z-index: 5;
    font-family: "bootstrap-icons";
    content: "\F62A";
    bottom: 0;
    right: 0.31rem;
    font-size: 2rem;
    color: #76171a;
}
#quiz .quiz-q.item-correct:before {
    position: absolute;
    z-index: 2;
    bottom: 0;
    right: 0;
    content: "";
    height: 0;
    width: 0;
    border: 35px solid;
    border-color: transparent #48d7bd #48d7bd transparent;
    border-bottom-right-radius: 10px;
}
#quiz .quiz-q.item-correct:after {
    line-height: 1.4;
    position: absolute;
    z-index: 5;
    font-family: 'bootstrap-icons';
    content: "\F26E";
    bottom: 0;
    right: 0.31rem;
    font-size: 2rem;
    color: #1a6f60;
}
#quiz .quiz-q .choices {
    margin-top: 1rem;
    display: flex;
    flex-direction: column;
    align-items: flex-start;
}
#quiz input[type=radio] {
    position: absolute;
    visibility: hidden;
    display: none;
}
#quiz input[type=radio] + label {
    background: var(--black-white);
    display: inline-block;
    padding: 5px 12px;
    margin: 5px 0 10px 0;
    transition: all 0.1s linear;
    border-radius: 5px;
    box-shadow: var(--shadow-button);
    display: flex;
    align-items: center;
    gap: 0.7rem;
}
#quiz input[type=radio] + label:before {
    content: "\F28A";
    font-family: "bootstrap-icons";
    color: var(--main-link-color);
    display: inline-block;
}
#quiz input[type=radio] + label:hover {
    cursor: pointer;
}
#quiz input[type=radio]:checked + label {
    background: var(--main-link-color);
    color: var(--heading-inverse);
}
#quiz input[type=radio]:checked + label:before {
    content: "\F134";
    color: var(--heading-inverse);
}
#quiz .item-incorrect input[type=radio]:checked + label {
    background: #ff5c61;
}
#quiz .question {
    font-weight: normal;
    font-size: 1.4rem;
    font-family: 'Montserrat', sans-serif;
    margin: 1rem 0;
    display: inline-block;
}
#quiz .show-all .question {
    margin: 1rem 0 0;
}
#quiz .ans {
    width: 100%;
    display: none;
    margin-bottom: 1rem;
    color: var(--medium-contrast-color);
    font-style: italic;
    line-height: 1.6;
}
#quiz .ans:before {
    content: "\F26E";
    font-family: 'bootstrap-icons';
    font-size: 2rem;
    transform: translateY(9px);
    margin-right: 0.2rem;
    display: inline-block;
    color: var(--medium-contrast-color);
}
#quiz .item-incorrect .ans {
    color: var(--heading-color);
}
#quiz .submit button {
    display: block;
    outline: none;
    width: 300px;
    margin: 2rem auto;
    padding: 0.8em 1em;
    font-family: 'Montserrat', sans-serif;
    background: var(--medium-contrast-color);
    font-weight: bold;
    font-size: 1.2rem;
    border-radius: 5px;
}
#emc-score {
    text-align: center;
    opacity: 0;
    padding: 0;
    transition: all 0.55s ease;
}
#emc-score.new-score {
    opacity: 1;
    background: var(--black-white);
    color: var(--heading-color);
    padding: 2rem;
    border-radius: 10px;
    margin-bottom: 4rem;
    font-family: 'Montserrat', sans-serif;
    font-weight: bold;
    font-size: 1.4rem;
    box-shadow: var(--big-shadow);
}
#emc-score .ending {
    opacity: 0;
    transform: translateY(20px);
    animation-name: ending;
    animation-duration: 0.5s;
    animation-iteration-count: 1;
    animation-fill-mode: forwards;
    animation-delay: .7s;
}
@keyframes ending {
    to {
        opacity: 1;
        transform: translateY(0px);
    }
}
#emc-score .message {
    margin-top: 1rem;
}
#emc-score .message i {
    display: block;
    font-size: 10rem;
    margin-bottom: 1.3rem;
}
#quiz #emc-submit {
    position: relative;
    transition: all 0.33s ease;
    opacity: 0;
}
#quiz #emc-submit.ready-show {
    background: var(--main-link-color);
    transform: rotateX(360deg);
    cursor: pointer;
    color: var(--black-white);
    opacity: 1;
}
#quiz #emc-submit.ready-show:hover {
    background: var(--main-link-color-hover);
}
#quiz #emc-next {
    position: relative;
    transition: all 0.33s ease;
    background: var(--low-contrast-color);
}
#quiz #emc-next.ready-show {
    background: var(--main-link-color);
    cursor: pointer;
    color: var(--black-white);
}
#quiz #emc-next.ready-show:hover {
    background: var(--main-link-color-hover);
}
#quiz #emc-progress {
    height: 30px;
    background: var(--main-link-color-darker);
    width: 100%;
    position: relative;
    border-radius: 5px;
    overflow: hidden;
}
#quiz #emc-progress_inner {
    width: 0;
    height: 30px;
    background: var(--main-link-color);
    transition: width 0.33s cubic-bezier(0.68, -0.55, 0.265, 1.55);
}
#quiz #emc-progress_ind {
    position: absolute;
    display: block;
    width: 100%;
    font-size: 1.2rem;
    font-weight: bold;
    top: 0.06rem;
    left: 0;
    text-align: center;
    color: var(--black-white);
}
@media screen and (max-width: 840px) {
    #quiz .quiz-q.current {
        margin-top: 2rem;
    }
    #quiz .question {
        font-size: 1.3rem;
        line-height: 1.6;
        margin-top: 0;
    }
    #quiz input[type=radio] + label {
        padding: 0.7rem 1rem;
    }
    #quiz .submit button {
        width: 100%;
    }
}
</style>
<main>
    <div class="wrap">
        <div id="quiz">
            <div id="emc-score"></div>
            <div id="emc-progress"></div>
            <ul>
                <li class="quiz-q quiz-1 current" data-quiz-item>
                    <div class="question">St. Louis Blues won the Stanley Cup 2019. Who was the opponent?</div>
                    <div class="choices" data-choices='["Boston Bruins","Carolina Hurricanes","New York Islanders"]'>
                    <div class="ans">Boston Bruins played against St. Louis Blues</div>
                    </div>
                </li>
                <li class="quiz-q quiz-2" data-quiz-item>
                    <div class="question">What was Henrik Lundqvist's nickname?</div>
                    <div class="choices" data-choices='["The Great","The King","Red Light"]'>
                    <div class="ans">Henrik "The King" Lundqvist</div>
                    </div>
                </li>
                <li class="quiz-q quiz-3" data-quiz-item>
                    <div class="question">How many OT periods are played in Playoff games if the score is tied in regulation?</div>
                    <div class="choices" data-choices='["Infinite (until one team scores)","1 OT period and then shootout","2 OT periods and then shootout"]'>
                    <div class="ans">OT periods in playoffs are infinite (until one team scores)</div>
                    </div>
                </li>
                <li class="quiz-q quiz-4" data-quiz-item>
                    <div class="question">What infraction is not a penalty in the NHL?</div>
                    <div class="choices" data-choices='["Closing hand on puck","The team commits one face-off violation in a single face-off","Butt-ending"]'>
                    <div class="ans">The team commits one face-off violation (Two times is a penalty)</div>
                    </div>
                </li>
                <li class="quiz-q quiz-5" data-quiz-item>
                    <div class="question">Who scored most points in the 2019-2020 season?</div>
                    <div class="choices" data-choices='["Connor McDavid","Leon Draisaitl","Artemi Panarin"]'>
                    <div class="ans">Leon Draisaitl scored most points (110 points)</div>
                    </div>
                </li>
                <li class="quiz-q quiz-6" data-quiz-item>
                    <div class="question">Atlanta Trashers relocated in which year?</div>
                    <div class="choices" data-choices='["2010","2011","2012"]'>
                    <div class="ans">The relocation happened in 2011</div>
                    </div>
                </li>
                <li class="quiz-q quiz-7" data-quiz-item>
                    <div class="question">Where did Atlanta Trashers relocate?</div>
                    <div class="choices" data-choices='["Calgary","Colorado","Winnipeg"]'>
                    <div class="ans">Atlanta Trashers relocated to Winnipeg</div>
                    </div>
                </li>
                <li class="quiz-q quiz-8" data-quiz-item>
                    <div class="question">How many Stanley Cups did the swedish defenseman Nicklas Lidstrom who retired in 2012 win?</div>
                    <div class="choices" data-choices='["2 Stanley Cups","3 Stanley Cups","4 Stanley Cups"]'>
                    <div class="ans">Nicklas Lidstrom won 4 Stanley Cups with Detroit Red Wings</div>
                    </div>
                </li>
                <li class="quiz-q quiz-9" data-quiz-item>
                    <div class="question">Which arena has the highest seating capacity?</div>
                    <div class="choices" data-choices='["United Center (Chicago Blackhawks)","Bell Centre (Montreal Canadiens)","Little Caesars Arena (Detroit Red Wings)"]'>
                    <div class="ans">Bell Centre (Montreal Canadiens) has the capacity of 21,105 people</div>
                    </div>
                </li>
                <li class="quiz-q quiz-10" data-quiz-item>
                    <div class="question">Which player thought his stick was on fire after scoring his 50th goal of the 2008-2009 season?</div>
                    <div class="choices" data-choices='["Alexander Ovechkin","Shea Weber","Sidney Crosby"]'>
                    <div class="ans">Alexander Ovechkin's stick was "on fire"</div>
                    </div>
                </li>
                <li class="quiz-q quiz-11" data-quiz-item>
                    <div class="question">What former NHL player is famous for his legendary mullet?</div>
                    <div class="choices" data-choices='["Mario Lemieux","Joe Sakic","Jaromir Jagr"]'>
                    <div class="ans">Jaromir Jagr had the most glorified mullet</div>
                    </div>
                </li>
                <li class="quiz-q quiz-12 last" data-quiz-item>
                    <div class="question">What year was Brock Boeser (Vancouver Canucks) drafted?</div>
                    <div class="choices" data-choices='["2015","2016","2017"]'>
                    <div class="ans">Brock Boeser was drafted in 2015</div>
                    </div>
                </li>
            </ul>
            <div class="submit">
                <button id="emc-next">Next</button>
                <button id="emc-submit">Submit Answers</button>
            </div>
        </div>
    </div>
</main>
<script>
$(document).ready(function() {
    (function($) {
    $.fn.emc = function(options) {
        
        var defaults = {
        key: [],
        scoring: "normal",
        progress: true
        },
        settings = $.extend(defaults,options),
        $quizItems = $('[data-quiz-item]'),
        $choices = $('[data-choices]'),
        itemCount = $quizItems.length,
        chosen = [],
        $option = null,
        $label = null;
        
    emcInit();
        
    if (settings.progress) {
        var $bar = $('#emc-progress'),
            $inner = $('<div id="emc-progress_inner"></div>'),
            $perc = $('<span id="emc-progress_ind">0/'+itemCount+'</span>');
        $bar.append($inner).prepend($perc);
        }
        
        function emcInit() {
        $quizItems.each( function(index,value) {
        var $this = $(this),
            $choiceEl = $this.find('.choices'),
            choices = $choiceEl.data('choices');
            for (var i = 0; i < choices.length; i++) {
            $option = $('<input name="'+index+'" id="'+index+'_'+i+'" type="radio">');
            $label = $('<label for="'+index+'_'+i+'">'+choices[i]+'</label>');
            $choiceEl.append($option).append($label);
                
            $option.on( 'change', function() {
                return getChosen();
            }); 
            }
        });
        }
        
        function getChosen() {
        chosen = [];
        $choices.each( function() {
            var $inputs = $(this).find('input[type="radio"]'),
            currentStep = $(this).parent(); //new
            $inputs.each( function(index,value) {
            if($(this).is(':checked')) {
                chosen.push(index + 1);
                updateStep(currentStep); //new
            }
            });
        });
        getProgress();
        }
        
        function getProgress() {
        var prog = (chosen.length / itemCount) * 100 + "%",
            $submit = $('#emc-submit');
        if (settings.progress) {
            $perc.text(chosen.length+'/'+itemCount);  
            $inner.css({width: prog});
        }
        if (chosen.length === itemCount) {
            $submit.addClass('ready-show');
            $submit.click( function(){
                $submit.fadeOut();
                return scoreNormal();
            });
        }
        }

        // show current step/hide other steps
        function updateStep(currentStep) {
            if(currentStep.hasClass('current')){
                $('#emc-next').addClass('ready-show');
                $('#emc-next').click( function(){
                    currentStep.removeClass('current');
                    currentStep.closest('li').next('li').addClass('current');
                    $(this).removeClass('ready-show');
                });
            }
            if(currentStep.hasClass('last')){
                $('#emc-next').hide();
            }
        }
        
        function scoreNormal() {
        var wrong = [],
            score = null,
            $scoreEl = $('#emc-score');
        for (var i = 0; i < itemCount; i++) {
            if (chosen[i] != settings.key[i]) {
            wrong.push(i);
            }
        }
        $quizItems.each( function(index) {
            var $this = $(this);
            if ($.inArray(index, wrong) !== -1 ) {
                $this.removeClass('item-correct').addClass('item-incorrect');
            } else {
                $this.removeClass('item-incorrect').addClass('item-correct');
            }
            $quizItems.removeClass('current');
            $quizItems.addClass('show-all');
            $('.ans').show();
        });
        
        score = ((itemCount - wrong.length) / itemCount).toFixed(2) * 100 + "%";

        var percentage = ((itemCount - wrong.length) / itemCount).toFixed(2) * 100;
        var message;
        
        if (percentage == "100") {
        message = '<i class="bi bi-emoji-sunglasses"></i> G.O.A.T'
        } else if (percentage >= "80") {
        message = '<i class="bi bi-emoji-laughing"></i> Deeecent'
        } else if (percentage >= "60") {
        message = '<i class="bi bi-emoji-smile"></i> Good enough'
        } else if (percentage >= "40") {
        message = '<i class="bi bi-emoji-expressionless"></i> Better luck next time'
        } else {
        message = '<i class="bi bi-emoji-dizzy"></i> Dude, come on..'
        }

        $scoreEl.html("<div class='ending'>You scored "+score+"<br><div class='message'>"+message+"</div></div>").addClass('new-score');

        $('html,body').animate({scrollTop: 0}, 500);
        $('#emc-progress').slideUp();
        }
    
    }
    }(jQuery));


    $(document).emc({
    key: ["1","2","1","2","2","2","3","3","2","1","3","1"]
    });
})
</script>
<?php if(isset( $_SERVER['HTTP_X_REQUESTED_WITH'] ) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest') {} else { include_once '../footer.php'; } ?>