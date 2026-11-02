// Crypto wallet and transaction models
package models

type CryptoWallet struct {
    ID        string  `json:"id"`
    UserID    string  `json:"user_id"`
    Address   string  `json:"address"`
    Balance   float64 `json:"balance"`
    Currency  string  `json:"currency"` // BTC, ETH, USDT
    Status    string  `json:"status"`   // active, frozen, closed
    CreatedAt string  `json:"created_at"`
    UpdatedAt string  `json:"updated_at"`
}

type CryptoTransaction struct {
    ID          string  `json:"id"`
    WalletID    string  `json:"wallet_id"`
    TxHash      string  `json:"tx_hash"`
    Type        string  `json:"type"`         // deposit, withdraw, transfer
    Amount      float64 `json:"amount"`
    Currency    string  `json:"currency"`
    Status      string  `json:"status"`       // pending, completed, failed
    ToAddress   string  `json:"to_address"`
    FromAddress string  `json:"from_address"`
    Fee         float64 `json:"fee"`
    CreatedAt   string  `json:"created_at"`
    ConfirmedAt string  `json:"confirmed_at"`
}