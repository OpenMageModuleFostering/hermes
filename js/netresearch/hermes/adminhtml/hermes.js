function handleHermesFormDisplay(radioButton)
{
	var innerForm = $('inner-hermes-form');
	if (innerForm == null) return false;
	
	if (radioButton.value == 0)
		Effect.toggle('inner-hermes-form', 'blind', { duration: 0.5 });
	if (radioButton.value == 1)
		Effect.toggle('inner-hermes-form', 'slide', { duration: 0.5 });
}