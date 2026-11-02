export function validateCardNumber(cardNumber: string): { valid: boolean; error?: string } {
  const cleanNumber = cardNumber.replace(/\s/g, '');
  
  if (!/^\d{13,19}$/.test(cleanNumber)) {
    return { valid: false, error: 'Card number must be 13-19 digits' };
  }
  
  let sum = 0;
  let isEven = false;
  
  for (let i = cleanNumber.length - 1; i >= 0; i--) {
    let digit = parseInt(cleanNumber[i], 10);
    
    if (isEven) {
      digit *= 2;
      if (digit > 9) {
        digit -= 9;
      }
    }
    
    sum += digit;
    isEven = !isEven;
  }
  
  if (sum % 10 !== 0) {
    return { valid: false, error: 'Invalid card number (failed Luhn check)' };
  }
  
  return { valid: true };
}

export function validateExpiry(expiry: string): { valid: boolean; error?: string } {
  if (!/^\d{2}\/\d{2}$/.test(expiry)) {
    return { valid: false, error: 'Expiry must be in MM/YY format' };
  }
  
  const [month, year] = expiry.split('/').map(Number);
  
  if (month < 1 || month > 12) {
    return { valid: false, error: 'Invalid month' };
  }
  
  const now = new Date();
  const currentYear = now.getFullYear() % 100;
  const currentMonth = now.getMonth() + 1;
  
  if (year < currentYear || (year === currentYear && month < currentMonth)) {
    return { valid: false, error: 'Card has expired' };
  }
  
  if (year > currentYear + 20) {
    return { valid: false, error: 'Expiry year too far in future' };
  }
  
  return { valid: true };
}

export function validateCVV(cvv: string, cardType?: string): { valid: boolean; error?: string } {
  const length = cardType === 'amex' ? 4 : 3;
  
  if (!/^\d+$/.test(cvv)) {
    return { valid: false, error: 'CVV must contain only digits' };
  }
  
  if (cvv.length !== length) {
    return { valid: false, error: `CVV must be ${length} digits` };
  }
  
  return { valid: true };
}

export function validateAmount(amount: number): { valid: boolean; error?: string } {
  if (amount <= 0) {
    return { valid: false, error: 'Amount must be greater than 0' };
  }
  
  if (amount > 100000000) {
    return { valid: false, error: 'Amount exceeds maximum limit' };
  }
  
  if (amount < 1000) {
    return { valid: false, error: 'Amount must be at least 1,000' };
  }
  
  if (!Number.isFinite(amount)) {
    return { valid: false, error: 'Invalid amount' };
  }
  
  return { valid: true };
}

export function formatCardNumber(value: string): string {
  const cleanValue = value.replace(/\D/g, '');
  const groups = cleanValue.match(/.{1,4}/g);
  return groups ? groups.join(' ') : cleanValue;
}

export function detectCardType(number: string): 'visa' | 'mastercard' | 'amex' | 'discover' | null {
  const cleanNumber = number.replace(/\D/g, '');
  
  if (/^4/.test(cleanNumber)) return 'visa';
  if (/^5[1-5]/.test(cleanNumber)) return 'mastercard';
  if (/^3[47]/.test(cleanNumber)) return 'amex';
  if (/^6(?:011|5)/.test(cleanNumber)) return 'discover';
  
  return null;
}

export function validateCardInput(cardNumber: string, expiry: string, cvv: string, amount: number) {
  const errors: string[] = [];
  
  const cardValidation = validateCardNumber(cardNumber);
  if (!cardValidation.valid) {
    errors.push(cardValidation.error!);
  }
  
  const expiryValidation = validateExpiry(expiry);
  if (!expiryValidation.valid) {
    errors.push(expiryValidation.error!);
  }
  
  const cardType = detectCardType(cardNumber);
  const cvvValidation = validateCVV(cvv, cardType || undefined);
  if (!cvvValidation.valid) {
    errors.push(cvvValidation.error!);
  }
  
  const amountValidation = validateAmount(amount);
  if (!amountValidation.valid) {
    errors.push(amountValidation.error!);
  }
  
  return {
    valid: errors.length === 0,
    errors
  };
}